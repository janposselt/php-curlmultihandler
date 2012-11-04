<?php

namespace Japo\Curl;

/**
 * Helper for handling parallel curl requests.
 * 
 * Register preinitialized curl handlers with addHandle and provide optional
 * success and error callbacks. These callbacks are fired as soon as the
 * appropiate curl handle finishs.
 */
class MultiHandler
{

    /**
     * The registered handles
     * 
     * @var array
     */
    protected $handles = array();

    /**
     * Adds a preinitialized curl handle to be executed.
     * 
     * There are optional success and error callbacks that are executed if
     * the curl call finishs. The callback receives the curl handle and the
     * curl result code (see CURLE_* constants) as parameters.
     * 
     * You should not throw an exception in your callbacks as it will stop
     * the further processing of the curl handlers.
     * 
     * @param resource $handle  The handle to register
     * @param callable $success An optional callback to execute on success
     * @param callable $error   An optional callback to execute on failure
     */
    public function addHandle($handle, $success = null, $error = null)
    {
        $this->handles[] = array(
            'handle' => $handle,
            'success' => $success,
            'error' => $error
        );
    }

    /**
     * Executes all registered curl handles.
     * 
     * If an handle is finished the optionally associated success or error
     * callback is fired.
     */
    public function execute()
    {
        $mh = curl_multi_init();

        $this->addToMulti($mh, $this->handles);

        $running = null;

        try {
            do {
                curl_multi_exec($mh, $running);

                $info = curl_multi_info_read($mh);

                if ($info) {
                    if ($info['result'] === CURLE_OK) {
                        $which = 'success';
                    }
                    else {
                        $which = 'error';
                    }

                    $callback = $this->findCallback($which, $info['handle'], $this->handles);
                    if (is_callable($callback)) {
                        call_user_func($callback, $info['handle'], $info['result']);
                    }

                    curl_close($info['handle']);
                }
            } while ($running > 0);
        }
        catch (Exception $exception) {
            $has_exception = true;
            
            foreach($this->handles as $handle) {
                @curl_close($handle['handle']);
            }
        }
        
        curl_multi_close($mh);
        
        if (isset($has_exception) && $exception) {
            throw $exception;
        }
    }

    /**
     * Adds the handles to the curl multi handle.
     * 
     * @param resource $multi_handle The multi handle returned by curl_multi_init()
     * @param array    $handles      The list of handles and callbacks
     */
    protected function addToMulti($multi_handle, $handles)
    {
        foreach ($handles as $handle) {
            curl_multi_add_handle($multi_handle, $handle['handle']);
        }
    }

    /**
     * Returns the propper callback defined by $which for the given handle.
     * 
     * @param string   $which   The callback type (e.g. error or success).
     * @param resource $handle  The curl handle to find the callback for.
     * @param array    $handles List of callbacks and handles.
     * 
     * @return callable|null Either the registered callback or null if none was found.
     */
    protected function findCallback($which, $handle, $handles)
    {
        foreach ($handles as $all_handles) {
            if ($all_handles['handle'] === $handle) {
                return $all_handles[$which];
            }
        }

        return null;
    }
    
    /**
     * Maps a constant value to its name.
     * 
     * In this context only CURL_* constants are returned.
     * 
     * @staticvar array $map
     * 
     * @param mixed $value The value to look up.
     * 
     * @return string The constant name
     */
    public static function getResultName($value) {
        static $map = null;
        
        if (!$map) {
            $prefix = 'CURLE_';
            $prefix_len = strlen($prefix);
            
            $constants = get_defined_constants(false);
            foreach($constants as $constant => $constant_value) {
                if (substr($constant, 0, $prefix_len) === $prefix && is_scalar($constant_value)) {
                    $map[$constant_value] = $constant;
                }
            }
        }
        
        if (isset($map[$value])) {
            return $map[$value];
        }
        
        return 'NOT_A_CONSTANT_VALUE';
    }

}