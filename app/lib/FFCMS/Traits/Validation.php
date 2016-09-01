<?php

namespace FFCMS\Traits;

use FFMVC\Helpers as Helpers;

/**
 * Handle validation (via GUMP or GUMP-inheriting validation class)
 *
 * The constructor should optional initialise default validation and filtering rules.
 * The class can then call the default method validate() which return true/false if validation passed
 * or validate(false) which returns an array of the validation errors which can be made
 * more presentable passed into validationErrors()
 *
 * @see \FFCMS\Helpers\Validator
 * @url https://github.com/Wixel/GUMP
 */
trait Validation
{
    /**
     * Validation rules (GUMP rules array)
     *
     * @var array
     */
    protected $validationRules = [];

    /**
     * Initial validation rules (automatically copied from $validationRules when instantiated)
     *
     * @var array
     */
    protected $validationRulesDefault = [];

    /**
     * Filter rules  (GUMP filters array)
     *
     * @var array
     */
    protected $filterRules = [];

    /**
     * Initial filter rules  (automatically copied from $filterRules when instantiated)
     *
     * @var array
     */
    protected $filterRulesDefault = [];

    /**
     * Boolean flag from validation run results if object is valid
     *
     * @var boolean
     */
    protected $valid = false;

    /**
     * Errors from last validation run
     *
     * @var bool|array
     */
    protected $validationErrors;


    /**
     * initialize
     */
    public function __construct()
    {
        // save default validation rules and filter rules in-case we add rules
        $this->validationRulesDefault = $this->validationRules;
        $this->filterRulesDefault = $this->filterRules;
    }


    /**
     * Get filter rules array
     *
     * @return array
     */
    public function filterRulesGet(): array
    {
        return $this->filterRules;
    }


    /**
     * Get default filter rules array
     *
     * @return array
     */
    public function filterRulesDefault(): array
    {
        return $this->filterRulesDefault;
    }


    /**
     * Get validation rules array
     *
     * @return array
     */
    public function validationRulesGet(): array
    {
        return $this->validationRules;
    }


    /**
     * Get default validation rules array
     *
     * @return array
     */
    public function validationRulesDefault(): array
    {
        return $this->validationRulesDefault;
    }


    /**
     * Set filter rules from array
     *
     * @param array $rules
     * @return array
     */
    public function filterRules(array $rules = []): array
    {
        $this->filterRules = $rules;

        return $this->filterRules;
    }


    /**
     * Set validation rules from array
     *
     * @param array $rules
     * @return array
     */
    public function validationRules(array $rules = []): array
    {
        $this->validationRules = $rules;

        return $this->validationRules;
    }


    /**
     * Reset filter rules to default
     *
     * @return array
     */
    public function filterReset(): array
    {
        $this->filterRules = $this->filterRulesDefault;

        return $this->filterRules;
    }


    /**
     * Reset validation rules to default
     *
     * @return array
     */
    public function validationReset(): array
    {
        $this->validationRules = $this->validationRulesDefault;

        return $this->validationRules;
    }


    /**
     * Add extra filter rules from array
     *
     * @param array $rules
     * @return array
     */
    public function filterRulesAdd(array $rules = []): array
    {
        $this->filterRules = array_merge($this->filterRules, $rules);

        return $this->filterRules;
    }


    /**
     * Add extra validation rules from array
     *
     * @param array $rules
     * @return array
     */
    public function validationRulesAdd(array $rules = []): array
    {
        $this->validationRules = array_merge($this->validationRules, $rules);

        return $this->validationRules;
    }


    /**
     * Enforce validation required validation check on given fields
     * or all fields if no array passed in
     *
     * @param array optional $fields
     * @return array $validationRules
     */
    public function validationRequired(array $fields = []): array
    {
        $rules = $this->validationRules;

        // force all fields to required if empty
        if (empty($fields)) {
            $fields = array_keys($rules);
        }

        // appened 'required' to validation rules for fields
        foreach ($rules as $k => $v) {
            if (in_array($k, $fields)) {
                $rules[$k] = 'required|' . $v;
            } elseif (false !== \UTF::instance()->stristr($v, 'exact_len')) {
                // special case, exact_len means required otherwise!
                unset($rules[$k]);
            }
        }

        $this->validationRules = $rules;

        return $this->validationRules;
    }


    /**
     * Apply filter rules only
     *
     * @param array $data
     * @param array $rules
     * @return array $data
     */
    public function filter(array $data = [], array $rules = []): array
    {
        if (empty($data) && method_exists($this, 'cast')) {
            $data = $this->cast();
        }

        $validator = Helpers\Validator::instance();
        $validator->filter_rules(empty($rules) ? $this->filterRules : $rules);
        return $validator->filter($data);
    }


    /**
     * Filter, then validate
     *
     * @param boolean $run GUMP - call 'run' (return true/false) otherwise call 'validate' (return array of errors)
     * @param array $data optional data array if different values to check outside of this mapper object fields
     * @param array $validationRules
     * @param array $filterRules
     * @return boolean|array of validated data if 'run' otherwise array of errors or boolean if passed 'validate'
     * @link https://github.com/Wixel/GUMP
     */
    public function validate($run = true, array $data = [], array $validationRules = [], array $filterRules = [])
    {
        if (empty($data) && method_exists($this, 'cast')) {
            $data = $this->cast();
        }

        $validator = Helpers\Validator::instance();
        $validator->validation_rules(empty($validationRules) ? $this->validationRules : $validationRules);
        $validator->filter_rules(empty($filterRules) ? $this->filterRules : $filterRules);

        if (!empty($run)) {
            // return boolean success/failure after validation
            $this->valid = is_array($validator->run($validator->filter($data)));
            return $this->valid;
        } else {
            // return array of errors if fail rathern than false
            $this->validationErrors = $validator->validate($validator->filter($data));
            $this->valid = !is_array($this->validationErrors);
            return $this->valid ? true : $this->validationErrors;
        }
    }


    /**
     * Process errors of results from (return array of $this->validate(true)) $validator->run($data) into friendlier notifications
     *
     * @param mixed $errors errors from $validator->run($data) or get last errors
     * @return array $notifications
     */
    public function validationErrors($errors = []): array
    {
        if (empty($errors)) {
            $errors = $this->validationErrors;
            if (empty($errors)) {
                return [];
            }
        }

        $notifications = [];

        if (is_array($errors)) {
            foreach ($errors as $e) {
                $fieldname = ucwords(str_replace('_', ' ', $e['field']));

                switch ($e['rule']) {

                    case 'validate_exact_len':
                        $msg = sprintf(_('%s must be exactly %d characters in length.'),
                            $fieldname, $e['param']);
                        break;

                    case 'validate_min_len':
                        $msg = sprintf(_('%s must be at least %d characters.'),
                            $fieldname, $e['param']);
                        break;

                    case 'validate_max_len':
                        $msg = sprintf(_('%s must at most %d characters.'),
                            $fieldname, $e['param']);
                        break;

                    case 'validate_valid_url':
                        $msg = sprintf('URLs must be valid if set.', $fieldname);
                        break;

                    case 'validate_valid_email':
                        $msg = sprintf(_('%s must be a valid email address.'),
                            $fieldname);
                        break;

                    case 'validate_required':
                        $msg = sprintf(_('%s must be entered.'), $fieldname);
                        break;

                    case 'validate_valid_name':
                        $msg = sprintf(_('%s must be a valid name.'),
                            $fieldname);
                        break;

                    case 'validate_boolean':
                        $msg = sprintf(_('%s must be a valid boolean (0/1 false/true off/on yes/no).'),
                            $fieldname);
                        break;

                    default:
                        $msg = sprintf('Exception: %s %s %s', $fieldname,
                            $e['rule'], $e['param']);
                        break;
                }
                $notifications[] = $msg;
            }
        }
        return $notifications;
    }
}
