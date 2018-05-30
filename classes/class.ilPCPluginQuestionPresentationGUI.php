<?php
/**
 * Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv3, see docs/LICENSE
 */

/**
 * Plugin Question Presentation GUI
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 *
 */
class ilPCPluginQuestionPresentationGUI
{
    const PRESMODE_EDIT = 'edit';
    const PRESMODE_PRESENTATION = 'presentation';
    const PRESMODE_PRINT = 'print';
    const PRESMODE_PREVIEW = 'preview';
    const PRESMODE_OFFLINE = 'offline';


    /** @var  ilLanguage $lng */
    protected $lng;

    /** @var  ilCtrl $ctrl */
    protected $ctrl;

    /** @var  ilTemplate $tpl */
    protected $tpl;

    /** @var ilPCPluginQuestionPlugin */
    protected $plugin;

    /** @var ilAccessHandler */
    protected $access;

    /** @var ilTabsGUI */
    protected $tabs;

    /** @var ilToolbarGUI */
    protected $toolbar;

    /** @var ilObjUser  */
    protected $user;

    /** @var int question_id */
    protected $question_id;

    /** @var string presentation_mode */
    protected $presentation_mode;

    /**
     * Constructor
     *
     * @param ilPCPluginQuestionPlugin $a_plugin
     */
    public function __construct($a_plugin)
    {
        global $DIC;

        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->access = $DIC->access();
        $this->tabs = $DIC->tabs();
        $this->lng = $DIC->language();
        $this->user = $DIC->user();
        $this->toolbar = $DIC->toolbar();
        $this->tpl = $DIC['tpl'];

        $this->plugin = $a_plugin;
        $this->lng->loadLanguageModule('assessment');
    }

    /**
     * Set the question id
     * @param int $a_question_id
     * @return $this
     */
    public function setQuestionId($a_question_id)
    {
        $this->question_id = $a_question_id;
        return $this;
    }

    /**
     * Get the question_id
     * @return int
     */
    public function getQuestionId()
    {
        return $this->question_id;
    }

    /**
     * Get the presentation mode
     * @return string
     */
    public function getPresentationMode()
    {
        return $this->presentation_mode;
    }

    /**
     * Set the presentation mode
     * @param string $mode
     * @return $this
     */
    public function setPresentatioMode($a_mode)
    {
        $this->presentation_mode = $a_mode;
        return $this;
    }

    /**
     * Get the HTML code of the question
     */
    public function getHTML()
    {
        $question_gui = assQuestionGUI::_getQuestionGUI("", $this->getQuestionId());
        switch ($this->getPresentationMode())
        {
            case self::PRESMODE_EDIT:
            case self::PRESMODE_OFFLINE:
            case self::PRESMODE_PREVIEW:
            case self::PRESMODE_PRINT:
                return $question_gui->getPreview(true);

            case self::PRESMODE_PRESENTATION:
                return $question_gui->getPreview(true);
        }
    }
}