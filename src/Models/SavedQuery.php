<?php

namespace SqlAnalyzer\Models;

use Illuminate\Database\Eloquent\Model;

class SavedQuery extends Model
{
    protected $table = 'sql_analyzer_saved_queries';

    protected $fillable = [
        'name',
        'sql',
    ];
}
