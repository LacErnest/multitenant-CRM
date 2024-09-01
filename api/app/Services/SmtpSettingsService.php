<?php

namespace App\Services;

use App\Contracts\Repositories\SmtpSettingRepositoryInterface;
use App\DTO\Settings\SmtpSettingDTO;
use App\Models\EmailTemplate;
use App\Models\SmtpSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Crypt;

class SmtpSettingsService
{
    protected SmtpSettingRepositoryInterface $smtpSettingRepository;

    public function __construct(
        SmtpSettingRepositoryInterface $smtpSettingRepository
    ) {
        $this->smtpSettingRepository = $smtpSettingRepository;
    }

    /**
     * @param string $id
     * @return SmtpSetting
     */
    public function findById(string $id): SmtpSetting
    {
        return $this->smtpSettingRepository->firstById($id);
    }

    /**
     * @param string $id
     * @return SmtpSetting
     */
    public function findBySenderEmail(string $senderEmail): SmtpSetting
    {
        return $this->smtpSettingRepository->firstBy('sender_email', $senderEmail);
    }

    /**
     * @param string $id
     * @return SmtpSetting
     */
    public function findByIdOrNull(string $id): ?SmtpSetting
    {
        return $this->smtpSettingRepository->firstByIdOrNull($id);
    }

    /**
     * @return Collection
     */
    public function getAll(): Collection
    {
        return $this->smtpSettingRepository->getAll();
    }

    /**
     * @param SmtpSetting $settingDTO
     * @return SmtpSetting
     */
    public function create(SmtpSettingDTO $settingDTO): SmtpSetting
    {
        $smtpSettingData = $settingDTO->toArray();
        $smtpSettingData['smtp_password'] = Crypt::encryptString($smtpSettingData['smtp_password']);
        return $this->smtpSettingRepository->create($smtpSettingData);
    }

    /**
     * @param string $id
     * @param SmtpSetting $settingDTO
     * @return SmtpSetting
     */
    public function update(string $id, SmtpSettingDTO $settingDTO): SmtpSetting
    {
        $smtpSettingData = $settingDTO->toArray();
        if (!empty($settingDTO->smtp_password)) {
            $smtpSettingData['smtp_password'] = Crypt::encryptString($settingDTO->smtp_password);
        } else {
            unset($smtpSettingData['smtp_password']);
        }
        $this->smtpSettingRepository->update($id, $smtpSettingData);
        return $this->findById($id);
    }

    /**
     * @param string $id
     * @return SmtpSetting
     */
    public function markAsDefault(string $id): SmtpSetting
    {
        SmtpSetting::query()->update(['default' => false]);
        $this->smtpSettingRepository->update($id, ['default' => true]);
        return $this->findById($id);
    }

    /**
     * Delete given smtp setting if it is not already configured by some email templates
     * @param string $id
     * @return int
     */
    public function delete(string $id): int
    {
        $isConfiguredToBeUsed = EmailTemplate::whereHas('smtpSetting', function ($query) use ($id) {
            return $query->where('id', $id);
        })->exists();

        if ($isConfiguredToBeUsed) {
            throw new \Exception('Smtp settings cannot be deleted. Some email templates are configured to be used.');
        }

        return $this->smtpSettingRepository->delete($id);
    }
}
