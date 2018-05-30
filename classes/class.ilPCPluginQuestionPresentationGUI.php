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
 * @ilCtrl_isCalledBy ilPCPluginQuestionPresentationGUI: ilUIPluginRouterGUI
 */
class ilPCPluginQuestionPresentationGUI
{
    const PRESMODE_EDIT = 'edit';
    const PRESMODE_PRESENTATION = 'presentation';
    const PRESMODE_PRINT = 'print';
    const PRESMODE_PREVIEW = 'preview';
    const PRESMODE_OFFLINE = 'offline';

    const CMD_SHOW = 'returnToPage';
    const CMD_RESET = 'reset';
    const CMD_INSTANT_RESPONSE = 'instantResponse';
    const CMD_HANDLE_QUESTION_ACTION = 'handleQuestionAction';
    const CMD_GATEWAY_CONFIRM_HINT_REQUEST = 'gatewayConfirmHintRequest';
    const CMD_GATEWAY_SHOW_HINT_LIST = 'gatewayShowHintList';

    /** @var ilDB */
    protected $db;

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

    /** @var string presentation_mode */
    protected $presentation_mode = self::PRESMODE_PRESENTATION;


    /** @var int question_id */
    protected $question_id;

    /** @var int page_id */
    protected $page_id;


    /**
     * @var assQuestionGUI
     */
    protected $questionGUI;

    /**
     * @var assQuestion
     */
    protected $questionOBJ;

    /**
     * @var ilAssQuestionPreviewSettings
     */
    protected $previewSettings;

    /**
     * @var ilAssQuestionPreviewSession
     */
    protected $previewSession;

    /**
     * @var ilAssQuestionPreviewHintTracking
     */
    protected $hintTracking;


    /**
     * Constructor
     */
    public function __construct()
    {
        global $DIC;

        $this->db = $DIC->database();
        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->access = $DIC->access();
        $this->tabs = $DIC->tabs();
        $this->lng = $DIC->language();
        $this->user = $DIC->user();
        $this->toolbar = $DIC->toolbar();
        $this->tpl = $DIC['tpl'];

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
        if (!isset($this->question_id))
        {
            $this->question_id = $_GET['question_id'];
        }
        return $this->question_id;
    }

    /**
     * Set the current page id
     * @param int $a_id
     * @return $this
     */
    public function setPageId($a_id)
    {
        $this->page_id = $a_id;
        return $this;
    }

    /**
     * Get the current page id
     * @param int $a_id
     * @return $this
     */
    public function getPageId()
    {
        if (!isset($this->page_id))
        {
            $this->page_id = $_GET['page_id'];
        }
        return $this->page_id;
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
     * set the plugin object
     */
    public function setPlugin($a_plugin)
    {
        $this->plugin = $a_plugin;
    }

    /**
     * Get the plugin object
     */
    public function getPlugin()
    {
        if (!isset($this->plugin))
        {
            require_once('Customizing/global/plugins/Services/COPage/PageComponent/PCPluginQuestion/classes/class.ilPCPluginQuestionPlugin.php');
            $this->plugin = new ilPCPluginQuestionPlugin();
        }
        return $this->plugin;
    }

    /**
     * Initialize the presentation
     */
    public function init()
    {
        $this->questionGUI = assQuestion::instantiateQuestionGUI($this->getQuestionId());
        $this->questionOBJ = $this->questionGUI->object;

        switch ($this->getPresentationMode())
        {
            case self::PRESMODE_EDIT:
            case self::PRESMODE_OFFLINE:
            case self::PRESMODE_PREVIEW:
            case self::PRESMODE_PRINT:
                $this->questionGUI->setRenderPurpose(assQuestionGUI::RENDER_PURPOSE_PREVIEW);
                break;

            case self::PRESMODE_PRESENTATION:
                $this->questionGUI->setRenderPurpose(assQuestionGUI::RENDER_PURPOSE_DEMOPLAY);
                $this->questionGUI->populateJavascriptFilesRequiredForWorkForm($this->tpl);
                $this->questionGUI->setTargetGui($this);
                $this->questionGUI->setQuestionActionCmd(self::CMD_HANDLE_QUESTION_ACTION);

                $this->previewSettings = new ilAssQuestionPreviewSettings($this->getPlugin()->getParentRefId());
                $this->previewSettings->init();

                $this->previewSession = new ilAssQuestionPreviewSession($this->user->getId(), $this->getQuestionId());
                $this->previewSession->init();

                $this->questionGUI->setPreviewSession($this->previewSession);
                $this->questionOBJ->setShuffler($this->getQuestionAnswerShuffler());

                $this->hintTracking = new ilAssQuestionPreviewHintTracking($this->db, $this->previewSession);
                break;
        }

        $this->tpl->setCurrentBlock("ContentStyle");
        $this->tpl->setVariable("LOCATION_CONTENT_STYLESHEET", ilObjStyleSheet::getContentStylePath(0));
        $this->tpl->parseCurrentBlock();

        $this->tpl->setCurrentBlock("SyntaxStyle");
        $this->tpl->setVariable("LOCATION_SYNTAX_STYLESHEET", ilObjStyleSheet::getSyntaxStylePath());
        $this->tpl->parseCurrentBlock();
    }

    /**
     * Get the HTML code for presentation
     * This is called from ilPCPluginQuestionGUI
     */
    public function getHTML()
    {
        $tpl = $this->getPlugin()->getTemplate('tpl.question_presentation.html');

        if ($this->getPresentationMode() != self::PRESMODE_PRESENTATION)
        {
            // simple case: just show the blank question without functionality
            $tpl->setVariable('QUESTION_OUTPUT', $this->questionGUI->getPreview(true));
        }
        else
        {
            $tpl->setVariable('PREVIEW_FORMACTION', $this->getFormAction());

            $this->populatePreviewToolbar($tpl);
            $this->populateQuestionOutput($tpl);
            $this->populateQuestionNavigation($tpl);

            if( $this->isShowGenericQuestionFeedbackRequired() )
            {
                $this->populateGenericQuestionFeedback($tpl);
            }

            if( $this->isShowSpecificQuestionFeedbackRequired() )
            {
                $this->populateSpecificQuestionFeedback($tpl);
            }

            if( $this->isShowBestSolutionRequired() )
            {
                $this->populateSolutionOutput($tpl);
            }
        }

        return $tpl->get();
    }



    private function getFormAction()
    {
        $this->ctrl->setParameterByClass(get_class($this), 'ref_id', $this->getPlugin()->getParentRefId());
        $this->ctrl->setParameterByClass(get_class($this), 'question_id', $this->getQuestionId());
        $this->ctrl->setParameterByClass(get_class($this), 'page_id', $this->getPageId());
        return $this->ctrl->getFormActionByClass(array('ilUIPluginRouterGUI', get_class($this)));
    }

    /**
     * Get the link target for actions related to this question
     * @param string $cmd
     * @return string
     */
    private function getLinkTarget($cmd)
    {
        $this->ctrl->setParameterByClass(get_class($this), 'ref_id', $this->getPlugin()->getParentRefId());
        $this->ctrl->setParameterByClass(get_class($this), 'question_id', $this->getQuestionId());
        $this->ctrl->setParameterByClass(get_class($this), 'page_id', $this->getPageId());
        return $this->ctrl->getLinkTargetByClass(array('ilUIPluginRouterGUI', get_class($this)), $cmd);
    }

    /**
     * return to the page showing the question
     */
    private function returnToPage()
    {
        $this->ctrl->setParameterByClass('ilLmPresentationGUI', 'ref_id', $this->getPlugin()->getParentRefId());
        $this->ctrl->setParameterByClass('ilLmPresentationGUI', 'obj_id', $this->getPageId());
        $this->ctrl->redirectByClass(array('ilLmPresentationGUI','ilLmPresentationGUI'));
    }

    /**
     * Show the embedding page
     */
    private function showPage()
    {
        $this->ctrl->returnToParent($this);
    }

    /**
     * Execute a command for this question
     * @throws ilCtrlException
     */
    public function executeCommand()
    {
        $this->init();

        $this->lng->loadLanguageModule('content');

        $nextClass = $this->ctrl->getNextClass($this);
        switch($nextClass)
        {
            default:

                $cmd = $this->ctrl->getCmd().'Cmd';
                $this->$cmd();
        }
    }

    private function resetCmd()
    {
        $this->previewSession->setRandomizerSeed(null);
        $this->previewSession->setParticipantsSolution(null);
        $this->previewSession->setInstantResponseActive(false);

        ilUtil::sendInfo($this->lng->txt('qst_preview_reset_msg'), true);
        $this->returnToPage();
    }

    private function instantResponseCmd()
    {
        $this->questionOBJ->persistPreviewState($this->previewSession);
        $this->previewSession->setInstantResponseActive(true);
        $this->returnToPage();
    }

    private function handleQuestionActionCmd()
    {
        $this->questionOBJ->persistPreviewState($this->previewSession);
        $this->returnToPage();
    }

    private function populatePreviewToolbar(ilTemplate $tpl)
    {
        $toolbarGUI = new ilAssQuestionPreviewToolbarGUI($this->lng);
        $toolbarGUI->setFormAction($this->getFormAction());
        $toolbarGUI->setResetPreviewCmd(self::CMD_RESET);
        $toolbarGUI->build();
        $tpl->setVariable('PREVIEW_TOOLBAR', $toolbarGUI->getHTML());
    }

    private function populateQuestionOutput(ilTemplate $tpl)
    {
        $questionHtml = $this->questionGUI->getPreview(true, $this->isShowSpecificQuestionFeedbackRequired());
        $tpl->setVariable('QUESTION_OUTPUT', $questionHtml);
    }

    private function populateSolutionOutput(ilTemplate $tpl)
    {
        $questionHtml =  $this->questionGUI->getSolutionOutput(0);
        $tpl->setCurrentBlock('solution_output');
        $tpl->setVariable('TXT_CORRECT_SOLUTION', $this->lng->txt('tst_best_solution_is'));
        $tpl->setVariable('SOLUTION_OUTPUT', $questionHtml);
        $tpl->parseCurrentBlock();
    }

    private function populateQuestionNavigation(ilTemplate $tpl)
    {
        require_once 'Modules/TestQuestionPool/classes/class.ilAssQuestionRelatedNavigationBarGUI.php';
        $navGUI = new ilAssQuestionRelatedNavigationBarGUI($this->ctrl, $this->lng);

        $navGUI->setInstantResponseCmd(self::CMD_INSTANT_RESPONSE);
        $navGUI->setInstantResponseEnabled($this->previewSettings->isInstantFeedbackNavigationRequired());

        $tpl->setVariable('QUESTION_NAVIGATION', $navGUI->getHTML());
    }

    private function populateGenericQuestionFeedback(ilTemplate $tpl)
    {
        if( $this->questionOBJ->isPreviewSolutionCorrect($this->previewSession) )
        {
            $feedback = $this->questionGUI->getGenericFeedbackOutputForCorrectSolution();
            $cssClass = ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_CORRECT;
        }
        else
        {
            $feedback = $this->questionGUI->getGenericFeedbackOutputForIncorrectSolution();
            $cssClass = ilAssQuestionFeedback::CSS_CLASS_FEEDBACK_WRONG;
        }

        if( strlen($feedback) )
        {
            $tpl->setCurrentBlock('instant_feedback_generic');
            $tpl->setVariable('GENERIC_FEEDBACK', $feedback);
            $tpl->setVariable('ILC_FB_CSS_CLASS', $cssClass);
            $tpl->parseCurrentBlock();
        }
    }

    private function populateSpecificQuestionFeedback(ilTemplate $tpl)
    {
        $tpl->setCurrentBlock('instant_feedback_specific');
        $tpl->setVariable('ANSWER_FEEDBACK', $this->questionGUI->getSpecificFeedbackOutput(0, -1));
        $tpl->parseCurrentBlock();
    }

    private function isShowBestSolutionRequired()
    {
        if( !$this->previewSettings->isBestSolutionEnabled() )
        {
            return false;
        }

        return $this->previewSession->isInstantResponseActive();
    }

    private function isShowGenericQuestionFeedbackRequired()
    {
        if( !$this->previewSettings->isGenericFeedbackEnabled() )
        {
            return false;
        }

        return $this->previewSession->isInstantResponseActive();
    }

    private function isShowSpecificQuestionFeedbackRequired()
    {
        if( !$this->previewSettings->isSpecificFeedbackEnabled() )
        {
            return false;
        }

        return $this->previewSession->isInstantResponseActive();
    }

    public function saveQuestionSolution()
    {
        $this->questionOBJ->persistPreviewState($this->previewSession);
    }

    /**
     * @return ilArrayElementShuffler
     */
    private function getQuestionAnswerShuffler()
    {
        require_once 'Services/Randomization/classes/class.ilArrayElementShuffler.php';
        $shuffler = new ilArrayElementShuffler();

        if( !$this->previewSession->randomizerSeedExists() )
        {
            $this->previewSession->setRandomizerSeed($shuffler->buildRandomSeed());
        }

        $shuffler->setSeed($this->previewSession->getRandomizerSeed());

        return $shuffler;
    }
}