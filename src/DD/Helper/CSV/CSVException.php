<?php

namespace DD\Helper\CSV;

use Exception;

class CSVException extends Exception
{
    const FILE_NOT_EXISTS = 1;
    const INVALID_PARAM = 2;
    const WRITE_ERROR = 3;
}
