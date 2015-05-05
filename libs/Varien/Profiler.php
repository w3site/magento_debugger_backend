<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Varien
 * @package    Varien_Profiler
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Varien_Profiler
{

    /**
     * Timers for code profiling
     *
     * @var array
     */
    static private $_timers = array();
    static private $_enabled = false;
    static private $_memory_get_usage = false;
    static private $_uniqId = null;
    static private $_jsonLog = null;
    
    public static function enable()
    {
        $serverKey = MagentoDebugger::getKeyFromString(isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'console');
        self::$_enabled = true;
        self::$_memory_get_usage = function_exists('memory_get_usage');
        $time = time();
        self::$_uniqId = $time . '_' . uniqid();
        self::$_jsonLog = MagentoDebugger::getDebuggerVarDir() . '/profiler/' . $serverKey . '.' . self::$_uniqId;
        
        $url = (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'Not specified');
        
        $profilerHeader = array(
            'time' => $time,
            'url'  => $url,
            'key'  => self::$_uniqId
        );
        
        file_put_contents(self::$_jsonLog . '.jshe', json_encode($profilerHeader));
    }

    public static function disable()
    {
        self::$_enabled = false;
    }

    public static function reset($timerName)
    {
        self::$_timers[$timerName] = array(
            'start'=>false,
            'count'=>0,
            'sum'=>0,
            'realmem'=>0,
            'emalloc'=>0,
        );
    }

    public static function resume($timerName)
    {
        if (!self::$_enabled) {
            return;
        }

        if (empty(self::$_timers[$timerName])) {
            self::reset($timerName);
        }
        if (self::$_memory_get_usage) {
            self::$_timers[$timerName]['realmem_start'] = memory_get_usage(true);
            self::$_timers[$timerName]['emalloc_start'] = memory_get_usage();
        }
        self::$_timers[$timerName]['start'] = microtime(true);
        self::$_timers[$timerName]['count'] ++;
    }

    public static function start($timerName)
    {
        //if ($timerName == 'CONFIGURABLE:Mage_Catalog_Model_Product_Type_Configurable::getUsedProducts'){
        //	file_put_contents('var/debug/prof', Varien_Debug::backtrace(true) . "\n", FILE_APPEND);
        //}
        //file_put_contents('var/debug/profiling.log', $timerName . " (s)\n", FILE_APPEND);
        self::resume($timerName);
    }

    public static function pause($timerName)
    {
        if (!self::$_enabled) {
            return;
        }
        
        $time = microtime(true); // Get current time as quick as possible to make more accurate calculations

        if (empty(self::$_timers[$timerName])) {
            self::reset($timerName);
        }
        if (false!==self::$_timers[$timerName]['start']) {
            self::$_timers[$timerName]['sum'] += $time-self::$_timers[$timerName]['start'];
            self::$_timers[$timerName]['start'] = false;
            self::$_timers[$timerName]['name'] = $timerName;
            if (self::$_memory_get_usage) {
                self::$_timers[$timerName]['realmem'] += memory_get_usage(true)-self::$_timers[$timerName]['realmem_start'];
                self::$_timers[$timerName]['emalloc'] += memory_get_usage()-self::$_timers[$timerName]['emalloc_start'];
            }
            
            $base64 = (
                    json_encode(self::$_timers[$timerName])
                    );
            
            //file_put_contents('var/debug/profiling.log', $timerName . " (f - " . self::$_timers[$timerName]['sum'] . "s)\n", FILE_APPEND);
            file_put_contents(self::$_jsonLog . '.jsar', $base64 . "\n", FILE_APPEND);
        }
    }

    public static function stop($timerName)
    {
        self::pause($timerName);
    }

    public static function fetch($timerName, $key='sum')
    {
        if (empty(self::$_timers[$timerName])) {
            return false;
        } elseif (empty($key)) {
            return self::$_timers[$timerName];
        }
        switch ($key) {
            case 'sum':
                $sum = self::$_timers[$timerName]['sum'];
                if (self::$_timers[$timerName]['start']!==false) {
                    $sum += microtime(true)-self::$_timers[$timerName]['start'];
                }
                return $sum;

            case 'count':
                $count = self::$_timers[$timerName]['count'];
                return $count;

            case 'realmem':
                if (!isset(self::$_timers[$timerName]['realmem'])) {
                    self::$_timers[$timerName]['realmem'] = -1;
                }
                return self::$_timers[$timerName]['realmem'];

            case 'emalloc':
                if (!isset(self::$_timers[$timerName]['emalloc'])) {
                    self::$_timers[$timerName]['emalloc'] = -1;
                }
                return self::$_timers[$timerName]['emalloc'];

            default:
                if (!empty(self::$_timers[$timerName][$key])) {
                    return self::$_timers[$timerName][$key];
                }
        }
        return false;
    }

    public static function getTimers()
    {
        return self::$_timers;
    }

    /**
     * Output SQl Zend_Db_Profiler
     *
     */
    public static function getSqlProfiler($res) {
        if(!$res){
            return '';
        }
        $out = '';
        $profiler = $res->getProfiler();
        if($profiler->getEnabled()) {
            $totalTime    = $profiler->getTotalElapsedSecs();
            $queryCount   = $profiler->getTotalNumQueries();
            $longestTime  = 0;
            $longestQuery = null;

            foreach ($profiler->getQueryProfiles() as $query) {
                if ($query->getElapsedSecs() > $longestTime) {
                    $longestTime  = $query->getElapsedSecs();
                    $longestQuery = $query->getQuery();
                }
            }

            $out .= 'Executed ' . $queryCount . ' queries in ' . $totalTime . ' seconds' . "<br>";
            $out .= 'Average query length: ' . $totalTime / $queryCount . ' seconds' . "<br>";
            $out .= 'Queries per second: ' . $queryCount / $totalTime . "<br>";
            $out .= 'Longest query length: ' . $longestTime . "<br>";
            $out .= 'Longest query: <br>' . $longestQuery . "<hr>";
        }
        return $out;
    }
}