<?php

namespace App\Exception;

interface ApiProblemExceptionInterface
{
    /**
     * Returns an api problem object.
     *
     * @return object api problem
     */
    public function getApiProblem();
}
