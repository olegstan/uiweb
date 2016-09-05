<?
namespace app\model;

use app\layer\LayerModel;

class ProjectPassword extends LayerModel implements Rules
{
    public function rules($scenario)
    {
        if ($scenario == 'insert') {
            return [
                ['field' => 'project_id', 'filter' => 'trim'],
                ['field' => 'name', 'filter' => 'trim'],
                ['field' => 'url', 'filter' => 'trim'],
                ['field' => 'description', 'filter' => 'trim'],
                ['field' => 'login', 'filter' => 'trim'],
                ['field' => 'password', 'filter' => 'trim'],
            ];
        } else if ($scenario == 'update') {
            return [
                ['field' => 'id', 'filter' => 'trim'],
                ['field' => 'name', 'filter' => 'trim'],
                ['field' => 'url', 'filter' => 'trim'],
                ['field' => 'description', 'filter' => 'trim'],
                ['field' => 'login', 'filter' => 'trim'],
                ['field' => 'password', 'filter' => 'trim'],
            ];
        } else if ($scenario == 'delete') {
            return [
                ['field' => 'id', 'filter' => 'trim'],
            ];
        }
    }

    /**
     * @param $scenario
     * @return mixed|void
     */

    public function validateRules($scenario)
    {

    }

    public function join()
    {

    }


    public function afterInsert()
    {
        $projectsPassword = new ProjectsPassword();
        $projectsPassword->project_id = $this->project_id;
        $projectsPassword->password_id = $this->id;
        $projectsPassword->insert();
    }

    public function afterDelete()
    {
        (new ProjectsPassword())->query->delete()->where('password_id = :password_id', [':password_id' => $this->id])->execute();
    }

}