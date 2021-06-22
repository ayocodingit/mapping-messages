<?php

namespace Ayocodingit\MappingMessages\App\Traits;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;

trait ValidationArray
{
    /**
     * limit
     *
     * @var int
     */
    protected $limit = 200;

    /**
     * result
     *
     * @var array
     */
    public $result = [
        'message' => 'Sukses membaca file import excel',
        'data' => [],
        'errors' => [],
        'errors_count' => 0,
        'number_row' => []
    ];

    /**
     * uniqueBy
     *
     * @var array
     */
    protected $uniqueBy = [];

    /**
     * index
     *
     * @var int|null
     */
    protected $index;

    /**
     * itemsValid
     *
     * @var array
     */
    protected $itemsValid = [];

    /**
     * isArrayReturn
     *
     * @var bool
     */
    public $isArrayReturn = false;


    /**
     * rules
     *
     * @return array
     */
    public function rules() {}

    /**
     * uniqueBy
     *
     * @return string|null
     */
    public function uniqueBy() {}

    /**
     * setData
     *
     * @param  mixed $data
     * @return void
     */
    public function setData(array $data)
    {
        $this->result['data'][] = $data;
    }


    /**
     * setItemsValid
     *
     * @param  mixed $rows
     * @return void
     */
    protected function setItemsValid($rows)
    {
        $this->itemsValid = $this->getItemsValidated($rows);
    }

    /**
     * setError
     *
     * @param  mixed $message
     * @return void
     */
    protected function setError($message)
    {
        if (is_array($message)) {
            $this->result['errors'][$this->index] = $message;
        } else {
            $this->result['errors'][$this->index][] = $message;
        }

        ++$this->result['errors_count'];
    }

    /**
     * setMessage
     *
     * @param  mixed $message
     * @return void
     */
    protected function setMessage($message)
    {
        $this->result['message'] = $message;
    }


    /**
     * setUp
     *
     * @return void
     */
    protected function setUp()
    {
        $this->setIndex();

        $this->initError();

        $this->setNumberRow();
    }

    /**
     * setNumberRow
     *
     * @return void
     */
    protected function setNumberRow()
    {
        $this->result['number_row'][] = $this->index + 1;
    }

    /**
     * setIndex
     *
     * @return void
     */
    protected function setIndex()
    {
        if (!$this->index) {
            $this->index = 0;
            return;
        }

        $this->index++;
    }

    /**
     * setArrayReturn
     *
     * @param  bool $isArray
     * @return void
     */
    protected function setArrayReturn(bool $isArray)
    {
        $this->isArrayReturn = $isArray;
    }

    /**
     * isRowsError
     *
     * @return bool
     */
    protected function isRowsError(): bool
    {
        return $this->result['errors'][$this->index] != null;
    }

    /**
     * getUniqueBy
     *
     * @param  mixed $rows
     * @return mixed
     */
    protected function getUniqueBy($rows): mixed
    {
        return $rows[$this->uniqueBy()] ?? null;
    }

    /**
     * getItemsValidated
     *
     * @param  mixed $rows
     * @return void
     */
    protected function getItemsValidated(array $rows)
    {
        $keyRules = array_keys($this->rules());

        $items = collect($rows)->only($keyRules);

        return $this->isArrayReturn ? $items->toArray() : $items;
    }

    /**
     * getNumberRow
     *
     * @return int
     */
    protected function getNumberRow(): int
    {
        return $this->result['number_row'][$this->index];
    }


    /**
     * getStatusCodeResponse
     *
     * @return int
     */
    protected function getStatusCodeResponse() : int
    {
        if ($this->result['errors_count'] > 0) {
            return Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        return Response::HTTP_OK;
    }

    /**
     * validated
     *
     * @param  mixed $rows
     * @return void
     */
    public function validated(array $rows)
    {
        App::setLocale(App::getLocale());

        $validator = Validator::make($rows, $this->rules());

        $this->setUp();

        $this->setItemsValid($rows);

        if ($validator->fails()) {
            $this->setError($validator->errors()->all());
        }

        $this->validateDuplicateError($validator->errors());
    }

    /**
     * duplicateValidateError
     *
     * @param  mixed $errors
     * @return void
     */
    protected function validateDuplicateError($errors)
    {
        if (!$this->uniqueBy()) {
            return;
        }

        $uniqueBy = isset($rows[$this->uniqueBy()]) && !$errors->get($this->uniqueBy());

        if ($uniqueBy) {
            $this->checkDuplicateError($this->itemsValid);
        }
    }

    /**
     * checkValidLimit
     *
     * @param  mixed $totals
     * @return void
     */
    protected function checkValidLimit($totals)
    {
        if (count($totals) >= $this->limit) {
            $message = __('validation.excel_data_limit', ['limit' => $this->limit]);
            abort(Response::HTTP_BAD_REQUEST, $message);
        }
    }

    /**
     * initError
     *
     * @return void
     */
    protected function initError()
    {
        $this->result['errors'][$this->index] = null;
    }

    /**
     * checkDuplicateError
     *
     * @param  mixed $rows
     * @return void
     */
    protected function checkDuplicateError($rows)
    {
        $uniqueBy = $this->getUniqueBy($rows);

        if (!$uniqueBy) {
            return;
        }

        $uniqueBy = strtoupper($uniqueBy);

        if (in_array($uniqueBy, $this->uniqueBy)) {
            $this->setError($this->index, __('validation.unique', ['attribute' => $this->uniqueBy()]));
        } else {
            $this->uniqueBy[] = $uniqueBy;
        }
    }
}