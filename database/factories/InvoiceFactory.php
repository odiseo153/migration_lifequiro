<?php

namespace Database\Factories;

use App\Models\{
    Patient,
    TypeOfTaxReceipt,
    Company,
    PaymentMethod,
    PreAuthorization
};
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'patient_id' => $this->faker->randomElement(Patient::pluck('id')->toArray()),
            'type_of_tax_receipt_id' => $this->faker->randomElement(TypeOfTaxReceipt::pluck('id')->toArray()),
            'payment_method_id' => $this->faker->randomElement(PaymentMethod::pluck('id')->toArray()),
            'note' => $this->faker->sentence,
            'invoice_token' => $this->faker->sentence,
            'pre_authorization_id' => null,
            'branch_id' => $this->faker->randomElement(Branch::pluck('id')->toArray()),
            'total' => $this->faker->randomNumber(5, true),
            'no_invoice' => $this->faker->randomNumber(4, true),
        ];
    }


    public function with_preauthorization()
    {
        return $this->state(function (array $attributes) {
            return [
                'pre_authorization_id' => $this->faker->randomElement(PreAuthorization::pluck('id')->toArray()),
            ];
        });
    }
}
