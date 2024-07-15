### 시작하기
- laravel new laravel-api
### 모델 만들기
- php artisan make:model Customer --all
- php artisan make:model Invoice --all
#### all 을 사용하면 model, factory, migration, seeder, request (store/ update), controller, policy 가 전부 생긴다 
### 모델 안의 코드들
```php
class Customer extends Model
{
    use HasFactory; // 팩토리 메서드 사용

    public function invoices(){
        // Customer 모델과 Invoice 모델관의 관계 정의
        // hasMany를 사용하면 하나의 고객이 여러개의
        // 송장을 가질 수 있음을 나타냄
        return $this->hasMany(Invoice::class);
        // Invoice::class -> invoice 모델의 클래스 이름
    }

}

class Invoice extends Model
{
    use HasFactory;

    public function customer(){
        // 하나의 송장은 하나의 고객에게 속한다
        return $this->belongsTo(Customer::class);
    }
}
```
### 마이그레이션
- up() : 
  - 새로운 테이블 생성
  - 컬럼 추가
  - 인덱스 생성
  - 기타 데이터베이스 스키마 변경 작업
- down():
  - 테이블 삭제
  - 컬럼 제거
  - 인덱스 제거
  - 기타 데이터베이스 스키마 변경을 되돌리는 작업
### 팩토리
- 테스트 데이터를 생성하는 데 사용됨
```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $type = $this->faker->randomElement(["I","B"]);
        $name = $type == 'I' ? $this->faker->name() : $this->faker->company();
        return [
            "name"=> $name,
            'type'=> $type,
            'email'=> $this->faker->safeEmail(),
            'city'=> $this->faker->city(),
            'address'=>$this->faker->address(),
            'state'=> $this->faker->state(),
            'postal_code'=> $this->faker->postcode()
        ];
    }
}

```
### 시더 
- php artisan migrate:fresh --seed
- migrate:fresh 는 모든 테이블을 삭제하고 새로 마이그레이션 한다는 의미
- --seed는 시더를 실행한다는 의미
- 데이터베이스 테이블에 초기 데이터 삽입
- 데이터베이스를 처음 생성할 때 또는 초기데이터가 필요한 경우 실행함
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() // 시더가 실행될때 호출되는 메서드
    {
        Customer::factory()
        ->count(25)
        ->hasInvoices(10)
        // 가상의 송장데이터가 생성되므로 송장시더를 실행시킬 필요는 없음
        ->create();
        // 25개의 가상 고객 데이터를 생성하고, 각 고객마다 10개의 가상 송장 데이터를 연결하여 데이터베이스에 삽입
    }
}

```
```php
<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CustomerSeeder::class);
    }
}
databaseSeeder를 통해서 여러 시더를 한 번에 실행가능
```
### api.php
- php artisan serve
- routes 나 API를 정의하는 곳
- URL안에 버전을 포함해야한다(버전별로 유니크한 주소가 있어야 하므로)
- routes를 api.php 안에서 정의하면 모든 주소는 api로 시작하게 된다.
```php
Route::group(['prefix'=> 'v1', 'namespace' => 'App\Http\Controllers\Api\V1'], function() {
    Route::apiResource('customers',CustomerController::class);
    Route::apiResource('invoices', InvoiceController::class);
});
```
- Route::group : 공통속성을 가진 라우트 그룹을 정의
- ['prefix'=> 'v1',  : 공통 접두사 설정 현재 상황에서는 이 그룹 안의 라우트는 /v1/으로 시작함
- 'namespace' => 'App\Http\Controllers\Api\V1'] : 이 라우트에 대한 컨트롤러 클래스가 위치한 폴더
- Route::apiResource('customers',CustomerController::class); : 리소스에 대한 RESTful 라우트를 생성함
  - get, post, put, delete 를 CustomerController의 메서드와 매핑해준다.
  - /api/v1/customers로의 get 요청은 CustomerController의 index메서드를 호출함
  - /api/v1/customers로의 post 요청은 CustomerController의 store메서드를 호출함
  - put 요청은 update 메서드 호출
  - delete 요청은 destroy 메서드 호출
### Controller
```php
public function show(Customer $customer)
    {
        return $customer;
    }
```
- /api/v1/customers/1 로의 get 요청과 매칭됨
```php
public function index()
    {
        return new CustomerCollection(Customer::all());
        // 단순히 모든 정보를 다 준다.
        return new CustomerCollection(Customer::paginate());
        // 페이지 네이션이 가능하게 데이터를 준다
    }

```
```php
public function show(Customer $customer)
    {
        $includeInvoices = request()->query('includeInvoices');
        // request() 는 Illuminate\Http\request 클래스의 인스턴스를 반환하는 헬퍼함수
        // http요청에서의 입력값을 가져오거나 , 요청 메소드를 확인 할 수 있음
        if( $includeInvoices ){
            return new CustomerResource($customer->loadMissing('invoices'));
            // loadMissing은 이미 로드된 관계를 제외하고, 나머지만 로드하는 메서드, 중복로딩방지
        }
        return new CustomerResource($customer);
    }

```
### 리소스
- php artisan make:resource V1\CustomerResource
- Eloquent 모델을 JSON으로 변환하는데 사용하는 클래스
- 단일 항목을 나타낼때 사용한다.
- 리소스 클래스는 toArray 메서드를 구현해야한다. 이 메서드는 모델을 배열로 변환하여 JSON으로 반환한다
- 리소스를 만들었으면 Controller에 임포트 해주어야한다.
- customerResource에서
  - InvoiceResource::collection($this->whenLoaded('invoices')):
  - whenLoaded('invoices')는 고객과 연관된 송장 데이터가 로드되었을 때만 실행
  - InvoiceResource::collection(...)은 송장 데이터를 리소스 컬렉션으로 변환

### 컬렉션
- php artisan make:resource V1\CustomerCollection
- 모델 컬렉션을 json으로 변환할때 사용한다.
- 여러 항목을 묶어서 처리한다.
- map, filter,pluck 등을 활용하여 조작할 수 있다.
- 리소스를 만들고, 컬렉션을 만들면 자동으로 컬렉션에서 각 모델을 json을 변환할때 리소스를 사용한다.

### 필터링
```php
public function index(Request $request)
    {
        $filter = new CustomerQuery();
        $queryItems = $filter->transform($request); //[['column','operator','value']]
        if(count($queryItems) == 0) {
            return new CustomerCollection(Customer::paginate());
        }else{
            return new CustomerCollection(Customer::where($queryItems)->paginate());
            // where 는 데이터베이스에서 조건을 만족하는 레코드를 검색하는데 사용
            //$queryItems는 검색 조건을 담고 있는 배열
        }
    }
    // Customer::where([])->paginate() 랑 Customer::paginate() 랑 같은 동작을 해서 if문 필요 없다고 함
```

```php
<?php

namespace App\Services\V1;

use Illuminate\Http\Request;

class CustomerQuery {
    protected $allowedParams = [
        "name"=> ['eq'],
        'type'=> ['eq'],
        'email'=> ['eq'],
        'address'=> ['eq'],
        'city'=> ['eq'],
        'state'=> ['eq'],
        "postalCode"=> ['eq','gt','lt']
    ];

    protected $columnMap = [
        'postalCode'=> 'postal_code',
    ];

    protected $operatorMap = [
        'eq'=> '=',
        'lt'=> '<',
        'lte'=> '<=',
        'gt'=> '>',
        'gte'=> '>='
    ];

    public function transform(Request $request){
        $eloQuery = [];


        foreach($this->allowedParams as $param => $operators){
            $query = $request->query($param);
            if(!isset($query)){
                continue;
            }
            $column = $this->columnMap[$param]??$param;

            foreach($operators as $operator){
                if(isset($query[$operator])){
                    $eloQuery[] = [$column, $this->operatorMap[$operator], $query[$operator]];
                }
            }
        }

        return $eloQuery;
    }
}
```
- http://localhost:8000/api/v1/customers?postalCode[eq]=71476

### post
- php artisan make:request StoreCustomerRequest

```php 
public function store(StoreCustomerRequest $request)
{
    return new CustomerResource(Customer::create($request->all()));
}
```
- Customer 모델의 create 메서드를 호출하여 새로운 고객을 생성
- $request->all()은 요청에서 받은 모든 데이터를 배열로 반환함. 이 배열은 새로운 고객의 속성 값으로 사용
- 생성된 고객 정보를 CustomerResource 클래스를 통해 응답으로 반환
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
        // 요청을 항상 허용함
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "name"=> ["required","string"],
            // 필수이고, 문자열으로 받아야함
            "email"=> ["required","email"],
            "type"=> ["required",Rule::in(['I','B','i','b'])],
            // i나 b 여야 함
            'address'=> ['required'],
            'city'=> ['required'],
            'state'=> ['required'],
            'postalCode'=> ['required']
        ];
    }

    protected function prepareForValidation(){
        // Laravel 폼 요청 클래스에서 사용되는 특별한 메서드임
        // 유효성 검사 전에 요청데이터를 변환하거나 수정할수 있음
        // 데이터 변환이나 데이터 정규화에 사용함
        //데이터의 필드 이름을 바꾸거나, 우편번호를 특정형식으로 변환하거나, 대소문자를 통일하거나...
        $this->merge([
            'postal_code'=> $this->postalCode,
            'name'=>strtoupper($this->name)
            ]);
        //$this->merge() : 폼 요청 객체의 데이터를 다른 데이터와 병합(또는 덮어쓰기)하는 역할
    }
}

```
### put/patch
- php artisan make:request V1\UpdateCustomerRequest
```php
 public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->all());
    }
```
- put과 patch 둘 다 여기에 매칭됨

```php
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
``` 
- sometimes는 필드가 있을때만 유효성 검사를 수행함

### bulk
```php
Route::group(['prefix'=> 'v1', 'namespace' => 'App\Http\Controllers\Api\V1'], function() {
    Route::apiResource('customers',CustomerController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::post("invoices/bulk", ['uses' => 'InvoiceController@bulkStore']);
});
```
```php
// InvoiceController
public function bulkStore(Request $request){

}
```
```php

```