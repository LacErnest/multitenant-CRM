<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\ContactCreateRequest;
use App\Http\Requests\Contact\ContactGetRequest;
use App\Http\Requests\Contact\ContactUpdateRequest;
use App\Http\Requests\Contact\ContactDeleteRequest;
use App\Http\Resources\Contact\ContactResource;
use App\Models\Contact;
use App\Models\Customer;
use App\Services\ContactService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


class ContactController extends Controller
{
    protected ContactService $contactService;

    public function __construct(
        ContactService $contact_service
    ) {
        $this->contactService = $contact_service;
    }

    /**
     * Get a contact of customer of a specific company
     *
     * @param  string            $companyId
     * @param  ContactGetRequest $request
     * @param  Customer          $customer
     * @param  Contact           $contact
     *
     * @return ContactResource
     */
    public function index(
        string $companyId,
        ContactGetRequest $request,
        Customer $customer,
        Contact $contact
    ): ContactResource {
        $customer = $this->contactService->get($customer, $contact);

        return ContactResource::make($customer);
    }

    /**
     * Create a new contact of a specific company
     *
     * @param  string               $companyId
     * @param  ContactCreateRequest $request
     * @param  Customer             $customer
     * @return ContactResource
     */
    public function create(
        string $companyId,
        ContactCreateRequest $request,
        Customer $customer
    ): ContactResource {
        $contact = $this->contactService->create($request, $customer);

        return ContactResource::make($contact->load('customer'));
    }

    /**
     * Update a contact of a specific company
     *
     * @param  string               $companyId
     * @param  ContactUpdateRequest $request
     * @param  Customer             $customer
     * @param  Contact              $contact
     *
     * @return ContactResource
     */
    public function update(
        string $companyId,
        ContactUpdateRequest $request,
        Customer $customer,
        Contact $contact
    ): ContactResource {
        $customer = $this->contactService->update($request, $customer, $contact);

        return ContactResource::make($customer->load('customer'));
    }


    /**
     * Delete contacts of a specific company
     *
     * @param  string               $companyId
     * @param  ContactDeleteRequest $request
     * @param  Customer             $customer
     *
     * @return JsonResponse
     */
    public function delete(string $companyId, ContactDeleteRequest $request, Customer $customer)
    {
        $message = $this->contactService->delete($request, $customer);

        return response()->json($message, Response::HTTP_NO_CONTENT);
    }
}
