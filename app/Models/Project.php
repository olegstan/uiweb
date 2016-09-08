<?
namespace app\model;

use app\layer\LayerModel;

class Project extends LayerModel implements Rules
{
    /**
     * @param $scenario
     * @return array|mixed
     */

    public function rules($scenario)
    {
        switch ($scenario) {
            case 'insert':
                return [
                    ['field' => 'name', 'filter' => 'trim'],
                ];
                break;
            case 'update':
                return [
                    ['field' => 'id', 'filter' => 'trim'],
                    ['field' => 'name', 'filter' => 'trim'],
                ];
                break;
            case 'delete':
                return [
                    ['field' => 'id', 'filter' => 'trim'],
                ];
                break;
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


    public function beforeInsert()
    {
        if (empty($this->created_at)) {
            $this->created_at = time();
        } else {
            $this->modified_at = time();
        }
    }

    public function afterInsert()
    {
        $user_project = new UsersProject();
        $user_project->user_id = $this->core->auth->user->id;
        $user_project->project_id = $this->id;
        $user_project->insert();
    }

    public function afterDelete()
    {
        $projects_passwords = (new ProjectsPassword)->query->select()->where('project_id = :project_id', [':project_id' => $this->id])->execute()->all()->getField('password_id');

        if ($projects_passwords) {
            foreach ($projects_passwords as $project_password_id) {
                (new ProjectPassword())->query->delete()->where('id = :id', [':id' => $project_password_id])->execute();
            }
        }

        (new ProjectsPassword)->query->delete()->where('project_id = :project_id', [':project_id' => $this->id])->execute();

        (new UsersProject())->query->delete()->where('project_id = :project_id', [':project_id' => $this->id])->execute();
    }
}