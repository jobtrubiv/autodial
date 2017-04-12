<?php
/**
 * Модель 'DialSettings'
 */
class DialSettingsModel extends DialSettingsModelAbstract
{
    /**
     * @return DialSettingsEntity
     */
    public function getActiveSettings()
    {
        $opts = $this->getCond();

        $opts->where('active = ?', 'y')
            ->limit(1);

        return $this->getDialSettings($opts);
    }

    /**
     * @param int|DialSettingsEntity $dialSettigs
     * @param $dialSettigsData
     * @return FunctionResult
     */
    public function save($dialSettigs, $dialSettigsData)
    {
        $result = new FunctionResult();

        if (!$dialSettigs instanceof DialSettingsEntity){
            $dialSettigs = $this->getDialSettingsByDialSettings($dialSettigs);
        }

        if (!$dialSettigs->exists()){
            $result->add(TranslateModel::getTranslateMessageByCode('dial_settings_not_found'));
            return $result;
        }

        $_result = $this->updateDialSettingsByDialSettings($dialSettigs, $dialSettigsData);
        if ($_result->isError()){
            $result->add(TranslateModel::getTranslateMessageByCode('save_dial_settings_error'));
            return $result;
        }

        return $result;
    }


}