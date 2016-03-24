<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 24.03.2016
 * Time: 20:18
 */

namespace Esockets;


interface WorkManagerInterface
{
    /**
     * @param $key
     * @param callable $callback
     * @param array $params
     * @param array $options
     * @return mixed
     * Метод добавляет "работу" в стек
     */
    public function addWork($key, callable $callback, array $params, array $options);

    /**
     * @param $key
     * @return mixed
     * Метод удаляет "работу" из стека
     */
    public function deleteWork($key);

    /**
     * @return mixed
     * Метод запускает выполнение работ из стека
     */
    public function execWork();

}

class WorkManager implements WorkManagerInterface
{
    private $_list;

    public function addWork($key, callable $callback, array $params = [], array $options = [])
    {
        if (!isset($this->_list[$key])) {
            $options = [
                    'always' => false,
                    'interval' => 1000,
                    'executed' => 0,
                    'result' => true, // result need to complete work
                ] + $options;
            $this->_list[$key] = ['callback' => $callback, 'params' => $params, 'options' => $options];
            return true;
        } else {
            return false;
        }
    }

    public function deleteWork($key)
    {
        if (isset($this->_list[$key]))
            unset($this->_list[$key]);
        return true;
    }

    public function execWork()
    {
        $mt = microtime(true) * 1000;
        foreach ($this->_list as $key => &$item) {
            if ($item['options']['executed'] + $item['options']['interval'] <= $mt) {
                $item['options']['executed'] = microtime(true);
                error_log('EXEC WORK ' . $key);
                $result = call_user_func_array($item['callback'], $item['params']);
                if (!$item['options']['always'] && $result == $item['options']['result']) {
                    $this->deleteWork($key); // if work completed, then may be deleted
                }
            }
            unset($item); // unset symlink variable
        }
    }

}
