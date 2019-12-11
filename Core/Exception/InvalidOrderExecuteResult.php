<?php


namespace TopConcepts\Klarna\Core\Exception;


/**
 * @codeCoverageIgnore
 */
class InvalidOrderExecuteResult extends \Exception
{
    /** @var string */
    protected $type = '';

    /** @var array */
    protected $values = [];

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param array $values
     */
    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }



}