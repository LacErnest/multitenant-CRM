<?php


namespace App\Services;

use App\Contracts\Repositories\ContactRepositoryInterface;
use App\Http\Requests\Contact\ContactUpdateRequest;
use App\Http\Requests\Contact\ContactDeleteRequest;
use App\Models\Contact;
use App\Models\Customer;
use App\Repositories\ContactRepository;
use App\Http\Requests\Contact\ContactCreateRequest;
use Illuminate\Http\Request;

class ContactService
{
    /**
     * @deprecated
     * @var ContactRepository $contact_repository
     */
    protected ContactRepository $contact_repository;

    /**
     * @var ContactRepositoryInterface
     */
    private ContactRepositoryInterface $contactRepository;

    public function __construct(
        ContactRepository $customer_repository,
        ContactRepositoryInterface $contactRepository
    ) {
        $this->contact_repository = $customer_repository;
        $this->contactRepository = $contactRepository;
    }

    public function get(Customer $customer, Contact $contact)
    {
        return $this->contact_repository->get($customer, $contact);
    }

    public function create(ContactCreateRequest $request, Customer $customer)
    {
        return $this->contact_repository->create($request->allSafe(), $customer);
    }

    public function update(ContactUpdateRequest $request, Customer $customer, Contact $contact)
    {
        return $this->contact_repository->update($request->allSafe(), $customer, $contact);
    }

    public function delete(ContactDeleteRequest $array, Customer $customer)
    {
        return $this->contact_repository->delete($array, $customer);
    }

    public function suggest($company_id, Request $request, $value)
    {
        return $this->contact_repository->suggest($company_id, $request, $value);
    }
}
