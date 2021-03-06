<?php
/**
 * MIT License
 *
 * Copyright (c) 2020 FroxyNetwork
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author 0ddlyoko
 */

namespace Api\Controller;

use Web\Controller\ResponseController;
use Web\Core\Error;

class ServerConfig {
    private $loaded = false;
    private $error = false;
    private $errorType = [];
    private $json;
    private $parsedJson;
    /*
     * string (id) => [int (index), int (second index or -1)]
     */
    private $keys = [];
    private $vps = [];

    private function load() {
        // Return servers.json file
        $this->json = file_get_contents(API_DIR.DS."Config".DS."servers.json");
        $parsedJson = json_decode($this->json, true);
        if (!$parsedJson || json_last_error() != JSON_ERROR_NONE) {
            $this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
            $this->loaded = true;
            return;
        }
        // Test if json is valid
        if (!isset($parsedJson['types']) || !is_array($parsedJson['types'])) {
            $this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
            $this->loaded = true;
            return;
        }
        $copy = [
            "types" => [],
            "vps" => []
        ];
        foreach ($parsedJson['types'] as $i => $type) {
            $arr = [];
            if (!isset($type['id']) || !is_string($type['id'])) {
                $this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
                $this->loaded = true;
                return;
            }
            $arr['id'] = $type['id'];
            if (array_key_exists($arr['id'], $this->keys)) {
                // Key already exists
                $this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
                $this->loaded = true;
                return;
            }
            $this->keys[$arr['id']] = [$i, -1];
            if (isset($type['database']) && !is_array($type['database'])) {
                $this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
                $this->loaded = true;
                return;
            }
            if (isset($type['database'])) {
                foreach ($type['database'] as $d) {
                    if (!is_string($d)) {
                        $this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
                        $this->loaded = true;
                        return;
                    }
                }
                $arr['database'] = $type['database'];
            }
            if (isset($type['variants']) && !is_array($type['variants'])) {
                $this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
                $this->loaded = true;
                return;
            }
            $var = [];
            if (isset($type['variants'])) {
                foreach ($type['variants'] as $j => $variant) {
                    $v = [];
                    if (!isset($variant['id']) || !is_string($variant['id'])) {
                        $this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
                        $this->loaded = true;
                        return;
                    }
                    $v['id'] = $variant['id'];
                    $this->keys[$v['id']] = [$i, $j];
                    if (isset($variant['database']) && !is_array($variant['database'])) {
                        $this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
                        $this->loaded = true;
                        return;
                    }
                    foreach ($variant['database'] as $d) {
                        if (!is_string($d)) {
                            $this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
                            $this->loaded = true;
                            return;
                        }
                    }
					
					if (!isset($variant['min']) || !is_integer($variant['min'])) {
						$this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
						$this->loaded = true;
						return;
					}
					if (!isset($variant['max']) || !is_integer($variant['max'])) {
						$this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
						$this->loaded = true;
						return;
					}
					
                    $v['database'] = $variant['database'];
					$v['min'] = $variant['min'];
					$v['max'] = $variant['max'];
                    $var[] = $v;
                }
                $arr['variants'] = $var;
            }
			if (!isset($type['min']) || !is_integer($type['min'])) {
				$this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
				$this->loaded = true;
				return;
			}
			if (!isset($type['max']) || !is_integer($type['max'])) {
				$this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
				$this->loaded = true;
				return;
			}
			$arr['min'] = $type['min'];
			$arr['max'] = $type['max'];
            $copy['types'][] = $arr;
        }

        foreach ($parsedJson['vps'] as $i => $vps) {
            $arr = [];
            if (!isset($vps['id']) || !is_string($vps['id'])) {
                $this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
                $this->loaded = true;
                return;
            }
            $arr['id'] = $vps['id'];
            if (!isset($vps['max_servers']) || !is_integer($vps['max_servers'])) {
                $this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
                $this->loaded = true;
                return;
            }
            $arr['max_servers'] = $vps['max_servers'];
			// config
			if (isset($vps['config']) && !is_array($vps['config'])) {
				$this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
				$this->loaded = true;
				return;
			}
			$arr['config'] = [];
			foreach ($vps['config'] as $j => $config) {
				$c = [];
				
				if (isset($config['type']) && !is_string($config['type'])) {
					$this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
					$this->loaded = true;
					return;
				}
				if (isset($config['min']) && !is_integer($config['min'])) {
					$this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
					$this->loaded = true;
					return;
				}
				if (isset($config['max']) && !is_integer($config['max'])) {
					$this->error([ResponseController::SERVER_INTERNAL, Error::INTERNAL_SERVER_JSON]);
					$this->loaded = true;
					return;
				}
				$c['type'] = $config['type'];
				$c['min'] = $config['min'];
				$c['max'] = $config['max'];
				
				$arr['config'][] = $c;
			}
			
            $copy['vps'][] = $arr;
            $this->vps[] = $vps['id'];
        }
        $this->parsedJson = $copy;
        $this->loaded = true;
    }

    private function error($errorType) {
        $this->error = true;
        $this->errorType = $errorType;
    }

    public function getParsedJson() {
        if (!$this->loaded)
            $this->load();
        if ($this->error)
            return false;
        return $this->parsedJson;
    }

    public function exist($key) {
        if (!$this->loaded)
            $this->load();
        if ($this->error)
            return false;
        return array_key_exists($key, $this->keys);
    }

    public function get($name) {
        if (!$this->exist($name))
            return false;
        $key = $this->keys[$name];
        if ($key[1] == -1)
            return $this->parsedJson["types"][$key[0]];
        else
            return $this->parsedJson["types"][$key[0]]["variants"][$key[1]];
    }

    public function existVps($key) {
        if (!$this->loaded)
            $this->load();
        if ($this->error)
            return false;
        return in_array($key, $this->vps);
    }

    public function getErrorType() {
        if (!$this->loaded)
            $this->load();
        return $this->errorType;
    }
}