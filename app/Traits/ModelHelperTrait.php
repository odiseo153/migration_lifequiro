<?php

namespace App\Traits;

use Closure;
use App\Shared\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

trait ModelHelperTrait
{
    protected static $query;
    protected static $defaultFillable = ["id", "created_at", "updated_at"];

    protected static $operators = [
        '=',
        '<',
        '>',
        '<=',
        '>=',
        '<>',
        '!=',
        '<=>',
        'like',
        'like binary',
        'not like',
        'ilike',
        '&',
        '|',
        '^',
        '<<',
        '>>',
        '&~',
        'is',
        'is not',
        'rlike',
        'not rlike',
        'regexp',
        'not regexp',
        '~',
        '~*',
        '!~',
        '!~*',
        'similar to',
        'not similar to',
        'not ilike',
        '~~*',
        '!~~*',
        'between'
    ];

    /**
     * @param array $arrs
     */
    public static function createFromArray($arrs)
    {
        $dataResult = [];
        foreach ($arrs as $key => $arr) {
            $dataResult[] = self::create($arr);
        }
        return $dataResult;
    }

    

    private function isValidHour(string $hour): bool
{
    // Convertir la hora a minutos totales desde las 00:00
    $timeParts = explode(':', $hour);
    if (count($timeParts) !== 2) {
        return false; // Formato inválido
    }

    $hours = (int) $timeParts[0];
    $minutes = (int) $timeParts[1];

    // Validar que los minutos sean múltiplos de 30
    return $minutes % 30 === 0 && $hours >= 0 && $hours < 24;
}

    public function createWithRelationships(array $relations, array $attributes)
    {
        foreach ($relations as $key => $model) {
            if (!empty($attributes[$model])) {
                if (isArrayBidi($attributes[$model]))
                    $this->$model()->createMany($attributes[$model]);
                else
                    $this->$model()->create($attributes[$model]);
            }
        }
        return $this;
    }

    public function deleteOldFile(string $attribute)
    {
        if (isset($this->attributes[$attribute]) && $this->attributes[$attribute]) {
            $oldFilePath = $this->attributes[$attribute];
            if (Storage::exists($oldFilePath)) {
                Storage::delete($oldFilePath);
            }
        }
    }

    /**
     * Check if string is base64 encoded
     */
    public function isBase64(string $string): bool
    {
        // Check if it's a data URL (data:image/...)
        if (strpos($string, 'data:') === 0) {
            return true;
        }

        // Check if it's a valid base64 string
        return base64_encode(base64_decode($string, true)) === $string;
    }

    function insertRecursive(array $data)
    {
        $model = $this;
        foreach ($data as $relation => $values) {
            // Si el valor es un array y la relación existe en el modelo
            if (is_array($values) && method_exists($model, $relation)) {
                foreach ($values as $value) {
                    // Crear el registro relacionado
                    $inputs = File::saveByModelRequest($model, $value);
                    $relatedModel = $model->$relation()->create($inputs);

                    // Llamar recursivamente si el nuevo modelo tiene más relaciones
                    $this->insertRecursive($inputs);
                }
            }
        }
    }

    public static function upsertFromArray($arrs, $appendsField = [], $columnsCompare = ["id"])
    {
        $dataResult = [];
        foreach ($arrs as $key => $arr) {
            $dataResult[] = self::upsert(array_merge($arr, $appendsField),  uniqueBy: $columnsCompare);
        }
        return $dataResult;
    }

    public static function updateOrCreateFromArray($arrs, $appendsField = [], $columnsCompare = ["id"])
    {
        $dataResult = [];
        foreach ($arrs as $key => $arr) {
            $fields = array_merge($arr, $appendsField);
            $dataResult[] = self::updateOrCreate(array_columns($arrs, $columnsCompare), $fields);
        }
        return $dataResult;
    }

    public function list($orderField = 'id', string $order = 'DESC')
    {
        $perPage = Request()->input("per_page") ?? DEFAULT_PAGINATION_SIZE;
        $perPage = $perPage > PAGINATION_LIMIT ? PAGINATION_LIMIT : $perPage;

        $orderField = Request()->input("order_field") ?? $orderField;
        $order = Request()->input("order") ?? $order;

        //Validation
        $order = in_array(strtoupper($order), ['ASC', 'DESC']) ? $order : 'DESC';

        self::$query->orderBy($orderField, $order);
        return self::$query->paginate($perPage);
    }

    public function listWithoutPagination($orderField = 'id', string $order = 'desc')
    {
        $orderField = Request()->input("order_field") ?? $orderField;
        $order = Request()->input("order") ?? $order;

        // Validaciones
        $order = in_array(strtoupper($order), ['ASC', 'DESC']) ? $order : 'DESC';

        self::$query->orderBy($orderField, $order);

        return self::$query->get(); // ← ahora devuelve una Collection
    }


    public static function filter(bool $withReltions = true)
    {
        $model = (new self);
        self::$query = $withReltions ? static::query()->with($model->relations) : static::query();

        self::builderFilter(self::$query);

        return new self;
    }

    public function getAll($orderField = 'id', string $order = 'DESC')
    {
        return self::$query->orderBy($orderField, $order)->get();
    }


    private static function builderFilter(&$query)
    {
        /**
         * @var Model $model
         * */
        $model = (new self);
        $request = Request();
        $filtering = self::filtering($request->input());

        if (!empty($filtering)) {
            foreach ($filtering['fields'] as $key => $value) {
                $operator = $filtering['operators'][$key . "_operator"] ?? "";
                $operator = self::isValidOperator($operator) ?? '=';

                if (self::isAfillable($key, $model)) {
                    $query = self::whereClosure($query);
                    $query = $query($key, $operator, $value);
                }
            }
        }

        if (!empty($request->queryClosures)) {
            foreach ($request->queryClosures as $closure) {
                $query->where($closure);
            }
        }

        if (!empty($request->queryJson)) {
            foreach ($request->queryJson as $column => $values) {
                $columnJson = array_keys($values)[0];
                $value = array_values($values)[0];

                $query->where("$column->$columnJson", $value);
            }
        }

        $query = self::whereClosureHas($query, $request->queryClosuresHas);
    }

    /**
     * Determine if the field is a valid field from Model
     *
     * @param $field
     * @param Model $model
     * @return boolean
     */
    private static function isAfillable($field, Model $model)
    {
        return in_array($field, $model->getFillable()) || in_array($field, self::$defaultFillable) ? true : false;
    }

    /**
     *   What type of operations
     * * Determines what type operation by operator
     *
     * @param Builder $builder
     * @param string $key
     * @param string $operator
     * @param mixed $value

     * @return Builder
     */
    private static function whereTypes($builder, $key, $operator, $value)
    {
        if ($operator == 'ilike' || $operator == 'like') {
            $builder->where($key, $operator, "%" . $value . "%");
        } else if ($operator == 'or') {
            if (is_array($value))
                $builder->orWhereIn($key, $value);
            else
                $builder->orWhere($key, $value);
        } else if ($operator == 'not') {
            if (is_array($value))
                $builder->whereNotIn($key, $value);
            else
                $builder->whereNot($key, $value);
        } else if ($operator == 'between') {
            $value = explode(',', str_replace(['[', ']', "'", '"', " "], '', $value));
            if (is_array($value)) {
                $builder->whereBetween($key, $value);
            }
        } else if (is_array($value)) {
            $builder->whereIn($key, $value);
        } else {
            if (is_date($value))
                $builder->whereDate($key, $operator, $value);
            else
                $builder->where($key, $operator, $value);
        }

        return $builder;
    }

    /**
     * Filter by operator
     *
     * @param Builder $builder
     * @return Closure
     */
    private static function whereClosure($builder): Closure
    {
        return function ($key, $operator, $value) use ($builder) {
            return self::whereTypes($builder, $key, $operator, $value);
        };
    }

    /**
     * Filter by operator
     *
     * @param Builder $builder
     * @param $closuresHas
     * @return Builder
     */
    private static function whereClosureHas($builder, $closuresHas)
    {
        $closuresHas = is_array($closuresHas) ? $closuresHas : json_decode($closuresHas, true);
        if (!empty($closuresHas)) {
            foreach ($closuresHas as $table => $fields) {
                $builder = $builder->whereHas($table, function (Builder $model) use ($fields) {

                    $filtering = self::filtering($fields);
                    foreach ($fields as $key => $value) {
                        $operator = $filtering['operators'][$key . "_operator"] ?? "";
                        $operator = self::isValidOperator($operator) ?? '=';

                        if (self::isAfillable($key, $model->getModel())) {
                            self::whereTypes($model, $key, $operator, $value);
                        }
                    }
                });
            }
        }

        return $builder;
    }

    /**
     * Separates the fields and operators of the request
     *
     * @param array $filter
     * @return array
     */
    private static function filtering($filter)
    {
        $fields = array_filter(array_keys($filter), fn($key) => strpos($key, '_operator') === false);
        $operators = array_filter(array_keys($filter), fn($key) => strpos($key, '_operator') !== false);

        return [
            "fields" => array_intersect_key($filter, array_flip($fields)),
            "operators" => array_intersect_key($filter, array_flip($operators)),
        ];
    }


    public function handleFileAttribute(string $attribute, $value, string $folder = 'documents',$file_name = 'document')
    {
        // If value is null or empty, set to null and delete old file if exists
        if (is_null($value) || $value === '' || $value === 'null') {
            $this->deleteOldFile($attribute);
            $this->attributes[$attribute] = null;
            return;
        }

        // If it's a base64 string, store the new file
        if (is_string($value) && $this->isBase64($value)) {
            $this->deleteOldFile($attribute);
            $this->attributes[$attribute] = $this->storeBase64File($value, $folder, $file_name);
            return;
        }

        // If it's already a file path and hasn't changed, keep it
        if (is_string($value) && !$this->isBase64($value)) {
            $this->attributes[$attribute] = $value;
            return;
        }

        // Default case - set to null
        $this->attributes[$attribute] = null;
    }

    protected function storeBase64File(string $base64File, string $folder, string $file_name = 'document'): string
    {
        // Extrae el tipo de archivo y los datos base64
        $mimeType = null;
        $fileData = null;

        if (preg_match('/^data:([a-zA-Z0-9\/\.\-\+\;]+);base64,/', $base64File, $matches)) {
            $mimeType = $matches[1];
            $fileData = base64_decode(substr($base64File, strpos($base64File, ',') + 1));
        } else {
            // Si no tiene el prefijo data, asumimos que es base64 puro y tratamos de detectar el tipo después
            $fileData = base64_decode($base64File);
        }

        // Determina la extensión a partir del mime type si está disponible
        $extensions = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
            'image/bmp' => '.bmp',
            'image/svg+xml' => '.svg',
            'application/pdf' => '.pdf',
            'application/msword' => '.doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
            'application/vnd.ms-excel' => '.xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
            'application/vnd.ms-powerpoint' => '.ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '.pptx',
            'text/plain' => '.txt',
            'application/zip' => '.zip',
            'application/x-rar-compressed' => '.rar',
            'audio/mpeg' => '.mp3',
            'audio/wav' => '.wav',
            'video/mp4' => '.mp4',
            // Agrega más tipos si es necesario
        ];

        $ext = '.bin'; // Valor por defecto

        if ($mimeType && isset($extensions[$mimeType])) {
            $ext = $extensions[$mimeType];
        } elseif (!$mimeType && function_exists('finfo_open')) {
            // Si no se detectó el mimeType, intenta detectarlo con finfo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_buffer($finfo, $fileData);
            finfo_close($finfo);
            if (isset($extensions[$detectedMime])) {
                $ext = $extensions[$detectedMime];
            }
        }

        // Genera un nombre de archivo único
        $filename = $file_name . '_' . uniqid('', true) . $ext;
        $path = $folder . '/' . $filename;

        // Define la ruta de almacenamiento en la carpeta 'public'
        $filePath = public_path($path);

        // Asegúrate de que el directorio exista
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Elimina archivos existentes con el mismo nombre base (sin el uniqid)
        $this->deleteExistingFiles($directory, $file_name, $ext);

        // Guarda el archivo en el disco
        file_put_contents($filePath, $fileData);

        return "/{$path}";
    }

    /**
     * Elimina archivos existentes con el mismo nombre base
     *
     * @param string $directory
     * @param string $file_name
     * @param string $ext
     * @return void
     */
    protected function deleteExistingFiles(string $directory, string $file_name, string $ext): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $pattern = $file_name . '_*' . $ext;
        $files = glob($directory . '/' . $pattern);

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * If is a valid operator return a operator but is not a valid operator return false
     *
     * @param string $operator
     * @return string|boolean
     */
    private static function isValidOperator(string $operator)
    {
        return in_array($operator, self::$operators) ? $operator : null;
    }
}
