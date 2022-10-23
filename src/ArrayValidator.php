<?php

declare(strict_types=1);

namespace Ipejasinovic;

class ArrayValidator {

    private static function _validate($validate_struct, $test_data, $path = ''): array {
        if (!is_array($validate_struct) || !is_array($test_data)) {
            return [false, ['Invalid inputs']];
        }
        $valid = true;
        $errors = [];
        foreach ($validate_struct as $key => $value) {
            if (is_array($value)) {
                if (!isset($test_data[$key])) {
                    self::invalidate($valid, $errors, sprintf("Field %s not set", trim(sprintf("%s.%s", $path, $key), '.')));
                    continue;
                }
                if (!is_array($test_data[$key])) {
                    self::invalidate($valid, $errors, sprintf("Field %s it not array", trim(sprintf("%s.%s", $path, $key), '.')));
                    continue;
                }
                [$valid, $sub_errors] = self::_validate($value, $test_data[$key], $key);
                $errors = array_merge($errors, $sub_errors);
                continue;
            }
            $tests = explode('|', $value);
            if (in_array('required', $tests) && !isset($test_data[$key])) {
                self::invalidate($valid, $errors, sprintf("Field %s not set", trim(sprintf("%s.%s", $path, $key), '.')));
                continue;
            }
            if (!isset($test_data[$key])) {
                continue;
            }
            $diff_tests = array_diff($tests, ['required']);
            foreach ($diff_tests as $test) {
                $custom_test = explode(':', $test);
                if (count($custom_test) === 2) {
                    if (method_exists(self, $custom_test[0])) {
                        if (!self::{$custom_test[0]}($test_data[$key], $custom_test[1])) {
                            self::invalidate($valid, $errors, sprintf("Field %s doesn't pass %s test", trim(sprintf("%s.%s", $path, $key), '.'), $test));
                        }
                    } else {
                        self::invalidate($valid, $errors, sprintf("Test %s for field %s doesn't exist", $custom_test[0], trim(sprintf("%s.%s", $path, $key), '.')));
                    }
                    continue;
                }
                if (is_callable('is_' . $test)) {
                    $function = new \ReflectionFunction('is_' . $test);
                    if (!$function->invoke($test_data[$key])) {
                        self::invalidate($valid, $errors, sprintf("Field %s doesn't pass %s test", trim(sprintf("%s.%s", $path, $key), '.'), $test));
                    }
                } else if (method_exists(self, $test)) {
                    if (!self::{$test}($test_data[$key])) {
                        self::invalidate($valid, $errors, sprintf("Field %s doesn't pass %s test", trim(sprintf("%s.%s", $path, $key), '.'), $test));
                    }
                } else {
                    self::invalidate($valid, $errors, sprintf("Test %s for field %s doesn't exist", $custom_test[0], trim(sprintf("%s.%s", $path, $key), '.')));
                }
            }
        }
        return [$valid, $errors];
    }

    private static function invalidate(&$valid, &$errors, $message): void {
        $valid = false;
        $errors[] = $message;
    }

    public static function validate(array $validate_struct, array $test_data): array {
        return self::_validate($validate_struct, $test_data);
    }

    private static function alpha($value, $test_value = null): bool {
        return ctype_alpha($value);
    }

    private static function upper($value, $test_value = null): bool {
        return strcmp(strtoupper($value), $value) === 0;
    }

    private static function lower($value, $test_value = null): bool {
        return strcmp(strtolower($value), $value) === 0;
    }

    private static function test($value, $test_value): bool {
        return $value == $test_value;
    }

    private static function min($value, $test_value): bool {
        if (is_string($value) && strlen($value) < (int) $test_value) {
            return false;
        }
        if (is_numeric($value) && $value < $test_value) {
            return false;
        }
        if (is_countable($value) && count($value) < $test_value) {
            return false;
        }
        return true;
    }

    private static function max($value, $test_value): bool {
        if (is_string($value) && strlen($value) > (int) $test_value) {
            return false;
        }
        if (is_numeric($value) && $value > $test_value) {
            return false;
        }
        if (is_countable($value) && count($value) > $test_value) {
            return false;
        }
        return true;
    }

}
