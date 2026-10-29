<?php

declare(strict_types=1);

namespace Pricezaim\Sdk\Dto;

final readonly class ClientProfile
{
    /** @param array<string, mixed> $raw */
    public function __construct(
        public ?string $lastName,
        public ?string $firstName,
        public ?string $middleName,
        public ?string $gender,
        public ?string $inn,
        public ?string $maritalStatus,
        public ?string $children,
        public ?string $birthDate,
        public ?string $birthPlace,
        public ?string $passportSeries,
        public ?string $passportNumber,
        public ?string $passportDate,
        public ?string $passportAuthority,
        public ?string $passportCode,
        public ?string $regAddress,
        public ?string $regAddressFias,
        public ?string $resAddress,
        public ?string $resAddressFias,
        public ?string $consentPdDate,
        public ?string $creditHistoryDate,
        public ?string $creditHistoryEndDate,
        public ?bool $marketingAgreed,
        public ?string $employmentType,
        public ?string $employmentOrg,
        public ?string $employmentPosition,
        public ?int $incomeSalary,
        public ?int $incomeExtra,
        public ?string $pdlRelation,
        public ?string $pdlFamilyRelation,
        public ?string $pdlFio,
        public ?string $pdlPosition,
        public ?string $pdlOrg,
        public ?string $antifraudSession,
        public array $raw,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            lastName: ArrayValue::string($data, 'last_name'),
            firstName: ArrayValue::string($data, 'first_name'),
            middleName: ArrayValue::string($data, 'middle_name'),
            gender: ArrayValue::string($data, 'gender'),
            inn: ArrayValue::string($data, 'inn'),
            maritalStatus: ArrayValue::string($data, 'marital_status'),
            children: ArrayValue::string($data, 'children'),
            birthDate: ArrayValue::string($data, 'birth_date'),
            birthPlace: ArrayValue::string($data, 'birth_place'),
            passportSeries: ArrayValue::string($data, 'passport_series'),
            passportNumber: ArrayValue::string($data, 'passport_number'),
            passportDate: ArrayValue::string($data, 'passport_date'),
            passportAuthority: ArrayValue::string($data, 'passport_authority'),
            passportCode: ArrayValue::string($data, 'passport_code'),
            regAddress: ArrayValue::string($data, 'reg_address'),
            regAddressFias: ArrayValue::string($data, 'reg_address_fias'),
            resAddress: ArrayValue::string($data, 'res_address'),
            resAddressFias: ArrayValue::string($data, 'res_address_fias'),
            consentPdDate: ArrayValue::string($data, 'consent_pd_date'),
            creditHistoryDate: ArrayValue::string($data, 'credit_history_date'),
            creditHistoryEndDate: ArrayValue::string($data, 'credit_history_end_date'),
            marketingAgreed: ArrayValue::boolOrNull($data, 'marketing_agreed'),
            employmentType: ArrayValue::string($data, 'employment_type'),
            employmentOrg: ArrayValue::string($data, 'employment_org'),
            employmentPosition: ArrayValue::string($data, 'employment_position'),
            incomeSalary: ArrayValue::int($data, 'income_salary'),
            incomeExtra: ArrayValue::int($data, 'income_extra'),
            pdlRelation: ArrayValue::string($data, 'pdl_relation'),
            pdlFamilyRelation: ArrayValue::string($data, 'pdl_family_relation'),
            pdlFio: ArrayValue::string($data, 'pdl_fio'),
            pdlPosition: ArrayValue::string($data, 'pdl_position'),
            pdlOrg: ArrayValue::string($data, 'pdl_org'),
            antifraudSession: ArrayValue::string($data, 'antifraud_session'),
            raw: $data,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->raw;
    }
}
