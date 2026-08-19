<?php

namespace Datomatic\ActiveCampaign\Enums;

/**
 * What a note is attached to.
 *
 * @see https://developers.activecampaign.com/reference/create-a-note
 */
enum NoteRelType: string
{
    case Activity = 'Activity';
    case Deal = 'Deal';
    case DealTask = 'DealTask';
    case Contact = 'Subscriber';
    case Account = 'CustomerAccount';
}
