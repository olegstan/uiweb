<?php
namespace Uiweb\Model\DatabaseModels;

use Uiweb\Model\Types\DatabaseModel;

class Migration extends DatabaseModel
{
    /**
     * @var string
     */
    protected $table = 'migrations';
    /**
     * @var int
     */
    protected $batch_last;
    /**
     * @var int
     */
    protected $batch_next;
    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'batch',
        'migrated_at'
    ];
    /**
     * @return string
     */
    public $name;
    /**
     * @return string
     */
    public $batch;
    /**
     * @return string
     */
    public $migrated_at;
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @return string
     */
    public function getDate()
    {
        return $this->migrated_at;
    }
    /**
     * @param null $scenario
     */
    public function getRules($scenario = null)
    {
        return [];
    }
    /**
     * @return int
     */
    public function getLastBatch()
    {
        /** @var  $migration $this */
        $migration = $this->getQuery()
            ->select(['MAX(batch) AS batch_last'])
            ->limit()
            ->order('batch')
            ->execute()
            ->one()
            ->getResult();

       return $migration ? $migration->batch_last : 1;
    }
    /**
     * @return int
     */
    public function getNextBatch()
    {
        /** @var  $migration $this */
        $migration = $this->getQuery()
            ->select(['MAX(batch) AS batch_next'])
            ->limit()
            ->order('batch')
            ->execute()
            ->one()
            ->getResult();

        return $migration ? $migration->batch_next + 1 : 1;
    }
    /**
     * @param $batch
     * @return array|null
     */
    public function getByBatch($batch)
    {
        return $this->getQuery()
            ->select()
            ->where('batch = :batch', [':batch' => $batch])
            ->execute()
            ->all('name')
            ->getResult();
    }
}