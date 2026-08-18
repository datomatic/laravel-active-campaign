<?php

namespace Datomatic\ActiveCampaign\Enums;

enum FieldType: string
{
    case Text = 'text';
    case TextArea = 'textarea';
    case Date = 'date';
    case DateTime = 'datetime';
    case DropDown = 'dropdown';
    case MultiSelect = 'multiselect';
    case Radio = 'radio';
    case CheckBox = 'checkbox';
    case Hidden = 'hidden';
    case ListBox = 'listbox';
}
