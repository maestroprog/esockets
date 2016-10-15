<?php
/**
 * Created by PhpStorm.
 * User: yarullin
 * Date: 24.03.2016
 * Time: 20:18
 */

namespace maestroprog\esockets;


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

/**
 * Class WorkManager
 * @package maestroprog\esockets
 *
 * Вспомогательный класс для работы как с серверной, так и с клиентской частью сокетов
 */
class WorkManager implements WorkManagerInterface
{
    private $_list;

    public function addWork($key, callable $callback, array $params = [], array $options = [])
    {
        if (!isset($this->_list[$key])) {
            $options = [
                    'always' => false,
                    'count' => 0, // default unlimited count of execution
                    'iterations' => 0,
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

    /**
     * Функция запускает выполнение всех заданий.
     * Все задания выполняются с заданным минимальным интервалом. Саму функцию можно вызывать с любым интервалом.
     * Задачи имеют параметр always, если он установлен в true, то задача является многоразовой,
     *      и будет выполняться до тех пор, пока результат не будет соответствует установленному ожиданию,
     *      после чего она автоматически удаляется из списка
     * Задачу можно удалять вручную с помощью метода deleteWork()
     */
    public function execWork()
    {
        foreach ($this->_list as $key => &$item) {
            $mt = microtime(true) * 1000;
            if ($item['options']['executed'] + $item['options']['interval'] <= $mt) {
                $item['options']['executed'] = microtime(true);
                error_log('EXEC WORK ' . $key);
                $result = call_user_func_array($item['callback'], $item['params']);
                // если задача не многоразовая
                // и если результат удовлетворяет ожиданиям
                if (!$item['options']['always'] && $result == $item['options']['result']) {
                    $this->deleteWork($key); // if work completed, then may be deleted
                }
            }
            unset($item); // unset symlink variable
        }
    }

}
