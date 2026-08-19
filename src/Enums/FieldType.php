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

    /**
     * Types whose value must be picked from a set of options: creating one without calling
     * fields()->createOptions() leaves a field nobody can fill in.
     */
    public function requiresOptions(): bool
    {
        return in_array($this, [
            self::DropDown,
            self::MultiSelect,
            self::Radio,
            self::CheckBox,
            self::ListBox,
        ], true);
    }
}
