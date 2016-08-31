<?php

namespace FFCMS\Traits;

/**
 * Add user notifications messages, typically for user feedback from web pages.
 */
trait Notification
{
    /**
     * Notify user
     *
     * @param string|array $data multiple messages by 'type' => [messages] OR message string
     * @param string|null $type type of messages (success, error, warning, info) OR null if multiple $data
     * @return bool success
     */
    public function notify($data, $type = null)
    {
        if (is_array($data)) {
            return \FFMVC\Helpers\Notifications::instance()->addMultiple($data);
        } else {
            return \FFMVC\Helpers\Notifications::instance()->add($data, $type);
        }
    }
}
