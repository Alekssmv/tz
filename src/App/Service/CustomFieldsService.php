<?php

declare(strict_types=1);

namespace App\Service;

use AmoCRM\Client\AmoCRMApiClient;
use Exception;

class CustomFieldsService
{
    /**
     * Находит id кастомного поля по имени
     */
    public function findIdOfCustomField(string $entityType, AmoCRMApiClient $apiClient, string $fieldName): ?int
    {
        $customFieldsService = $apiClient->customFields($entityType);
        $customFields = $customFieldsService->get();
        $fieldId = null;
        foreach ($customFields as $customField) {
            if ($customField->getName() == $fieldName) {
                $fieldId = $customField->getId();
            }
        }
        if ($fieldId === null) {
            throw new Exception('Custom field not found');
        }
        return $fieldId;
    }

    /**
     * Проверяет существует ли кастомное поле
     */
    public function ifCustomFieldExists(string $entityType, AmoCRMApiClient $apiClient, string $fieldName): bool
    {
        $customFieldsService = $apiClient->customFields($entityType);
        $customFields = $customFieldsService->get();
        $fieldExists = false;
        foreach ($customFields as $customField) {
            if ($customField->getName() == $fieldName) {
                $fieldExists = true;
            }
        }
        return $fieldExists;
    }
}