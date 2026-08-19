<?php

namespace Datomatic\ActiveCampaign\Enums;

/**
 * @see https://developers.activecampaign.com/reference/bulk-import-status-info
 */
enum BulkImportStatus: string
{
    case Waiting = 'waiting';
    case Claimed = 'claimed';
    case Active = 'active';
    case Completed = 'completed';
    case Failed = 'failed';
    case Interrupted = 'interrupted';

    /**
     * The batch will not change state any more, whether it worked or not.
     */
    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Interrupted], true);
    }
}
