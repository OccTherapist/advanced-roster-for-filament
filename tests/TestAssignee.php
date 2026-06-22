<?php

namespace OccTherapist\AdvancedRosterForFilament\Tests;

use Illuminate\Database\Eloquent\Model;

class TestAssignee extends Model
{
    protected $table = 'test_assignees';

    protected $fillable = ['name'];
}
