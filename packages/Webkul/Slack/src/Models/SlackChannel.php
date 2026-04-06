<?php

namespace Webkul\Slack\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a configured Slack channel for CRM notifications.
 *
 * Part of the multi-channel support introduced in A7 of the
 * additional-integration spec.
 */
class SlackChannel extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'channel_name',
        'channel_id',
        'notify_on_new_lead',
        'notify_on_stage_change',
        'notify_on_assignment',
        'webhook_url',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'notify_on_new_lead'     => 'boolean',
        'notify_on_stage_change' => 'boolean',
        'notify_on_assignment'   => 'boolean',
        'is_active'              => 'boolean',
    ];

    /**
     * Scope to only active channels.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Returns the channel identifier to use when posting messages.
     * Prefers the channel_id (more reliable) over the channel_name.
     */
    public function getIdentifier(): string
    {
        return $this->channel_id ?: $this->channel_name;
    }
}
