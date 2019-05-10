<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaseRequest extends FormRequest
{

    public function expectsJson()
    {
        return true;
    }

    public function wantsJson()
    {
        return true;
    }
}
