<?php
use Illuminate\Http\Request;

if (!function_exists('filterEmpty')) {
    /**
     * Remove empty values from request inputs.
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    function filterEmpty(Request $request)
    {
        return array_filter($request->all(), function ($value) {
            return !empty($value);
        });
    }
}