<?php

namespace App\Models;

use App\Enums\ServicesStatus;
use App\Enums\AppointmentStatus;


class MedicalTerapiaTracionModule extends BaseModel
{
    protected $fillable = [
        'patient_id',
        'service_id',
        'acquired_service_id',
        'plan_item_id',
        'physical_therapy',
    ];

    public function physical_therapy_category()
    {
        return $this->belongsToMany(PhysicalTherapyCategory::class, 'medical_terapia_tracion_categories', 'terapia_module', 'category_id');
    }

    public function service()
    {
        return $this->belongsTo(PatientItem::class, 'service_id');
    }

    public function acquired_service()
    {
        return $this->belongsTo(AcquiredService::class, 'acquired_service_id');
    }

    public function plan_service()
    {
        return $this->belongsTo(Item::class, 'plan_item_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function checkAppointmentStatusOnService(AcquiredService $service,$serviceColumn,$serviceId): void
    {
        $patient = $service->patient;
        $sameAppointment = $patient->acquired_services()
            ->where($serviceColumn, '!=', $serviceId)
            ->get()
            ->filter(
                fn($s) => $s->appointment() !== null && $s->appointment()->id === $service->appointment()->id
            );

        if (
            $sameAppointment->isEmpty() || 
            $sameAppointment->every(fn($s) => $s->status == ServicesStatus::COMPLETADA)
            )
            {
            $service->appointment()->update(['status_id' => AppointmentStatus::COMPLETADA->value]);
        }
    }

    /**
     * Get categories with their ancestors to build complete hierarchy
     */
    private function getCategoriesWithAncestors($selectedCategories)
    {
        if (!$selectedCategories || $selectedCategories->isEmpty()) {
            return collect();
        }

        $allCategoriesIds = collect();
        $selectedIds = $selectedCategories->pluck('id');

        // For each selected category, get all its ancestors
        foreach ($selectedCategories as $category) {
            $allCategoriesIds = $allCategoriesIds->merge($this->getAncestorIds($category));
        }

        // Add the selected categories IDs
        $allCategoriesIds = $allCategoriesIds->merge($selectedIds)->unique();

        // Fetch all categories (selected + ancestors)
        $allCategories = PhysicalTherapyCategory::whereIn('id', $allCategoriesIds)->get();

        // Mark which categories are selected
        $allCategories->each(function($category) use ($selectedIds) {
            $category->is_selected = $selectedIds->contains($category->id);
        });

        return $allCategories;
    }

    /**
     * Recursively get all ancestor IDs of a category
     */
    private function getAncestorIds($category)
    {
        $ancestorIds = collect([$category->id]);

        if ($category->father_id) {
            $parent = PhysicalTherapyCategory::find($category->father_id);
            if ($parent) {
                $ancestorIds = $ancestorIds->merge($this->getAncestorIds($parent));
            }
        }

        return $ancestorIds;
    }

    public function getCategoriesHierarchy()
    {
        $categories = $this->physical_therapy_category;
        $categoriesWithAncestors = $this->getCategoriesWithAncestors($categories);

        if (!$categories || $categories->isEmpty()) {
            return [];
        }

        // Filter to get only root categories (categories with no father_id or father_id is null)
        $rootCategories = $categoriesWithAncestors->filter(function($category) {
            return is_null($category->father_id);
        });
        
        $result = [];
        
        foreach ($rootCategories as $rootCategory) {
            $result[] = $this->buildCategoryTree($rootCategory, $categoriesWithAncestors);
        }
        
        return $result;
    }

    /**
     * Get categories formatted for clinic history - categories without children are separated by |
     */
    public function getCategoriesForClinicHistory()
    {
        $categories = $this->physical_therapy_category;
        $categoriesWithAncestors = $this->getCategoriesWithAncestors($categories);

        if (!$categories || $categories->count() == 0) {
            return [];
        }

        // Filter to get only root categories (categories with no father_id or father_id is null)
        $rootCategories = $categoriesWithAncestors->filter(function($category) {
            return is_null($category->father_id);
        });
        
        $result = [];
        $flatCategories = [];
        
        foreach ($rootCategories as $rootCategory) {
            $processedCategory = $this->buildCategoryTreeForClinicHistory($rootCategory, $categoriesWithAncestors);
            if ($processedCategory) {
                if (is_string($processedCategory)) {
                    // This is a flat category (no children)
                    $flatCategories[] = $processedCategory;
                } else {
                    // This is a hierarchical category
                    $result[] = $processedCategory;
                }
            }
        }
        
        // If we have flat categories, add them as a string separated by |
        if (!empty($flatCategories)) {
            $result[] = implode(' | ', $flatCategories);
        }
        
        return $result;
    }
    
    private function buildCategoryTree(PhysicalTherapyCategory $category, $allCategories)
    {
        // Find children within all categories (including ancestors)
        $children = $allCategories->filter(function($cat) use ($category) {
            return $cat->father_id == $category->id;
        });
        
        $result = [
            'id' => $category->id,
            'name' => $category->name,
            'type' => $category->type,
            'description' => $category->description,
            'is_selected' => $category->is_selected ?? false,
            'children' => []
        ];
        
        foreach ($children as $child) {
            $result['children'][] = $this->buildCategoryTree($child, $allCategories);
        }
        
        return $result;
    }

    private function buildCategoryTreeForClinicHistory(PhysicalTherapyCategory $category, $allCategories)
    {
        // Find children within all categories (including ancestors)
        $children = $allCategories->filter(function($cat) use ($category) {
            return $cat->father_id == $category->id;
        });
        
        // If this category is selected and has no children, return just the name
        if (($category->is_selected ?? false) && $children->isEmpty()) {
            return $category->name;
        }
        
        // If this category has children, build the hierarchy
        if (!$children->isEmpty()) {
            $result = [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type,
                'description' => $category->description,
                'is_selected' => $category->is_selected ?? false,
                'children' => []
            ];
            
            foreach ($children as $child) {
                $childResult = $this->buildCategoryTreeForClinicHistory($child, $allCategories);
                if ($childResult) {
                    $result['children'][] = $childResult;
                }
            }
            
            // Only return if this category is selected or has selected children
            if (($category->is_selected ?? false) || count($result['children']) > 0) {
                return $result;
            }
        }
        
        return null;
    }

}
