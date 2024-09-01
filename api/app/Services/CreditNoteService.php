<?php


namespace App\Services;

use App\Repositories\CreditNoteRepository;

class CreditNoteService
{
    protected CreditNoteRepository $credit_note_repository;

    public function __construct(CreditNoteRepository $credit_note_repository)
    {
        $this->credit_note_repository = $credit_note_repository;
    }

    public function create($creditNoteXero)
    {
        return $this->credit_note_repository->create($creditNoteXero);
    }

    public function update($creditNoteXero)
    {
        return $this->credit_note_repository->update($creditNoteXero);
    }
}
