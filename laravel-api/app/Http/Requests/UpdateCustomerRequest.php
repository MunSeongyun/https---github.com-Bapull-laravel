<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $method = $this->method();
        if ($this->method() == "PUT") {
            return [
                "name"=> ["required","string"],
                "email"=> ["required","email"],
                "type"=> ["required",Rule::in(['I','B','i','b'])],
                'address'=> ['required'],
                'city'=> ['required'],
                'state'=> ['required'],
                'postalCode'=> ['required']
            ];
        }else{
            return [
                "name"=> ["sometimes", "required","string"],
                "email"=> ["sometimes","required","email"],
                "type"=> ["sometimes","required",Rule::in(['I','B','i','b'])],
                'address'=> ["sometimes",'required'],
                'city'=> ["sometimes",'required'],
                'state'=> ["sometimes",'required'],
                'postalCode'=> ["sometimes",'required']
            ];
        }

    }
    protected function prepareForValidation(){
        if($this->postalCode){
            $this->merge([
                'postal_code'=> $this->postalCode,
                'name'=>strtoupper($this->name)
                ]);
        }
       
    }
}
