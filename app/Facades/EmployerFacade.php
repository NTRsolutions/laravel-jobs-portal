<?php

namespace App\Facades;

use App\Entities\Company;
use App\Entities\Job;
use App\Services\ApplicationService;
use App\Services\CompanyService;
use App\Services\GeoLocationService;
use App\Services\JobService;
use Illuminate\Database\Eloquent\Model;

class EmployerFacade
{
    protected $jobService;
    protected $companyService;
    protected $geoLocationService;

    /**
     * EmployerFacade constructor.
     * @param JobService $jobService
     * @param CompanyService $companyService
     * @param GeoLocationService $geoLocationService
     */
    public function __construct(JobService $jobService, CompanyService $companyService, GeoLocationService $geoLocationService)
    {
        $this->jobService = $jobService;
        $this->companyService = $companyService;
        $this->geoLocationService = $geoLocationService;
    }

    /**
     * @param Company $company
     * @return string
     */
    public function getCompanyLogo(Company $company)
    {
        return $this->companyService->getLogo($company);
    }

    /**
     * @param array $data
     * @param Company $company
     * @return mixed
     */
    public function updateCompany(array $data, Company $company)
    {
        $this->companyService->validAndSaveLogo($data, $company);
        $data = $this->geoLocationService->validAndMerge($data);
        return $this->companyService->updateModel($data, $company);
    }

    /**
     * @param Company $company
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createCompanyJob(Company $company, array $data)
    {
        $newJob = $this->jobService->newModel($data);
        return $this->companyService->addNewJob($company, $newJob);
    }

    /**
     * @param array $data
     * @param Model $job
     * @return mixed
     */
    public function updateJob(array $data, Model $job)
    {
        $data = $this->geoLocationService->validAndMerge($data);
        $this->jobService->syncSkills($job, $data);
        return $this->jobService->updateModel($data, $job);
    }

    /**
     * @param null $occupationId
     * @param null $companyId
     * @param null $contractTypeId
     * @param null $locationId
     * @param null $search
     * @param int $experience
     * @return mixed
     */
    public function searchJobs($occupationId = null, $companyId = null, $contractTypeId = null, $locationId = null, $search = null, $experience = 0, $salaryRange = null)
    {
        $geoLocation = $this->geoLocationService->getModel($locationId);
        return $this->jobService->getSearchJobs($occupationId, $companyId, $contractTypeId, $geoLocation, $search, $experience, $salaryRange);
    }
}