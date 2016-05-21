<?php

namespace App\Repositories;


use App\Entities\GeoLocation;
use App\Entities\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class JobRepository extends BaseRepository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'App\Entities\Job';
    }

    /**
     * @param $companyId
     * @return mixed
     */
    public function getCompanyJobs($companyId)
    {
        return $this->model->with(['jobs.contractType', 'jobs.geoLocation', 'jobs.occupation'])
            ->whereCompanyId($companyId)
            ->paginate();
    }


    /**
     * @param Model $job
     * @param array $skills
     * @return mixed
     */
    public function syncSkills(Model $job, array $skills = array())
    {
        return $job->skills()->sync($skills);
    }

    /**
     * @param Job $job
     */
    public function getJobSkillsSelect(Job $job)
    {
        return $job->skills()->lists('id')->all();
    }

    /**
     * @param null $occupationId
     * @param null $companyId
     * @param null $contractTypeIds
     * @param null $search
     * @param int $experience
     * @param int $salaryMin
     * @param null $salaryMax
     * @return Builder
     */
    protected function defaultSearchJobs($occupationId = null, $companyId = null, $contractTypeIds = null, $search = null, $experience = 0, $salaryMin = 0, $salaryMax = null)
    {
        $query = $this->model->frequentJoins($occupationId, $companyId, $contractTypeIds, $experience, $salaryMin);

        if(! is_null($salaryMax)){
            $query->where('salary', '<=', $salaryMax);
        }

        if(! is_null($search) && !empty($search)){
            $query->where(function($query) use ($search) {
                $query->where('jobs.name', 'like', '%'.$search.'%')
                    ->Orwhere('jobs.description', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    /**
     * @param null $occupationId
     * @param null $companyId
     * @param null $contractTypeIds
     * @param null $search
     * @param int $experience
     * @param int $salaryMin
     * @param null $salaryMax
     * @return Collection
     */
    public function getAllSearchJobs($occupationId = null, $companyId = null, $contractTypeIds = null, $search = null, $experience = 0, $salaryMin = 0, $salaryMax = null)
    {
        return $this->defaultSearchJobs($occupationId, $companyId, $contractTypeIds, $search, $experience, $salaryMin, $salaryMax)->take(config('app.maxResults'))->get();
    }

    /**
     * @param null $occupationId
     * @param null $companyId
     * @param null $contractTypeIds
     * @param GeoLocation $location
     * @param null $search
     * @param int $experience
     * @param int $salaryMin
     * @param null $salaryMax
     * @return Collection
     */
    public function getAllSearchJobsNear($occupationId = null, $companyId = null, $contractTypeIds = null, GeoLocation $location, $search = null, $experience = 0, $salaryMin = 0, $salaryMax = null)
    {
        $query = $this->defaultSearchJobs($occupationId, $companyId, $contractTypeIds, $search, $experience, $salaryMin, $salaryMax);

        $query->selectRawDistance($location->lat, $location->lng)
            ->having('distance', '<', config('app.miles'))
            ->orderBy('distance', 'asc');

        return $query->take(config('app.maxResults'))->get();
    }

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllJobs()
    {
        return $this->model->with(['contractType', 'company', 'geoLocation', 'occupation'])->paginate();
    }

    public function getSalaryRange()
    {
        $min = money_format('%.2n', $this->model->select('salary')->orderBy('salary', 'asc')->take(1)->first()->salary);
        $max = money_format('%.2n', $this->model->select('salary')->orderBy('salary', 'desc')->take(1)->first()->salary);

        return ['salaryMin' => $min, 'salaryMax' => $max];
    }

    public function getExperienceRange()
    {
        $min = $this->model->select('experience')->orderBy('experience', 'asc')->take(1)->first()->experience;
        $max = $this->model->select('experience')->orderBy('experience', 'desc')->take(1)->first()->experience;

        return ['experienceMin' => $min, 'experienceMax' => $max];
    }
}
