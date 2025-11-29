<?php

namespace App\Repositories;

use Yakupeyisan\CodeIgniter4\EntityFramework\Repository\Repository;
use Yakupeyisan\CodeIgniter4\EntityFramework\Core\DbContext;
use App\Models\UserCustomField;
use Yakupeyisan\CodeIgniter4\EntityFramework\Query\IQueryable;

/**
 * UserCustomFieldRepository - UserCustomField entity için özel repository
 * One-to-One ilişki için repository
 */
class UserCustomFieldRepository extends Repository
{
    public function __construct(DbContext $context)
    {
        parent::__construct($context, UserCustomField::class);
    }

    /**
     * Kullanıcıya göre özel alanları getir
     */
    public function getByUserId(int $userId): ?UserCustomField
    {
        return $this->getAll()
            ->where(fn($ucf) => $ucf->UserId === $userId)
            ->firstOrDefault();
    }

    /**
     * Kullanıcı için özel alan oluştur veya güncelle
     */
    public function createOrUpdate(UserCustomField $userCustomField): void
    {
        $existing = $this->getByUserId($userCustomField->UserId);
        
        if ($existing) {
            // Güncelle
            $existing->CustomField01 = $userCustomField->CustomField01;
            $existing->CustomField02 = $userCustomField->CustomField02;
            $existing->CustomField03 = $userCustomField->CustomField03;
            $existing->CustomField04 = $userCustomField->CustomField04;
            $existing->CustomField05 = $userCustomField->CustomField05;
            $this->update($existing);
        } else {
            // Yeni oluştur
            $this->add($userCustomField);
        }
    }

    /**
     * Özel alanı güncelle
     */
    public function updateCustomField(int $userId, int $fieldNumber, string $value): bool
    {
        $userCustomField = $this->getByUserId($userId);
        
        if (!$userCustomField) {
            return false;
        }
        
        $propertyName = "CustomField0{$fieldNumber}";
        if (property_exists($userCustomField, $propertyName)) {
            $userCustomField->$propertyName = $value;
            $this->update($userCustomField);
            return true;
        }
        
        return false;
    }

    /**
     * Kullanıcının özel alanları var mı kontrol et
     */
    public function hasCustomFields(int $userId): bool
    {
        $customField = $this->getByUserId($userId);
        if (!$customField) {
            return false;
        }
        
        return !empty($customField->CustomField01) ||
               !empty($customField->CustomField02) ||
               !empty($customField->CustomField03) ||
               !empty($customField->CustomField04) ||
               !empty($customField->CustomField05);
    }
}

