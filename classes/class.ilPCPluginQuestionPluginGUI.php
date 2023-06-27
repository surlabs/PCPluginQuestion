<?php
/**
 * Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv3, see docs/LICENSE
 */

 
/**
 * Plugin Question Page Component GUI
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 *
 * @ilCtrl_IsCalledBy ilPCPluginQuestionPluginGUI: ilPCPluggedGUI
 */
class ilPCPluginQuestionPluginGUI extends ilPageComponentPluginGUI
{
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

    /** @var ilPropertyFormGUI */
    protected $form_gui;

	/**
	 * ilPCPluginQuestionPluginGUI constructor.
	 */
	public function __construct()
	{
		global $DIC;

        parent::__construct();

		$this->lng = $DIC->language();
		$this->ctrl = $DIC->ctrl();
        $this->access = $DIC->access();
        $this->tabs = $DIC->tabs();
        $this->lng = $DIC->language();
        $this->user = $DIC->user();
        $this->toolbar = $DIC->toolbar();

        $this->tpl = $DIC['tpl'];

        $this->lng->loadLanguageModule('assessment');
        $this->lng->loadLanguageModule('cont');
    }


	/**
	 * Execute command
	 */
	public function executeCommand()
	{
		$next_class = $this->ctrl->getNextClass();

		switch($next_class)
		{
			default:
				// perform valid commands
				$cmd = $this->ctrl->getCmd();

				// we need sub commands of 'insert' for the selection of questions from pools
                // otherwise ilPageEditorGUI will redirect to the page
                // because the page content is not yet created
				if (($cmd == 'insert') && $_GET["subCmd"] != '')
                {
                    $cmd = $_GET["subCmd"];
                }

				if (in_array($cmd, array("insert", "create", "save", "edit", "cancel",
                    "insertFromPool", "poolSelection", "selectPool", "copyQuestion")))
				{
					$this->$cmd();
				}
				else
                {
                    $this->tpl->setContent('unknown command: '. $cmd);
                }
				break;
		}
	}


    /**
     * Set insert tabs
     *
     * @param string $a_active active tab id
     */
    function setInsertTabs($a_active)
    {
        // new question
        $this->tabs->addSubTab("new_question",
            $this->lng->txt("cont_new_question"),
            $this->ctrl->getLinkTarget($this, "insert"));

        // copy from pool
        $this->ctrl->setParameter($this, "subCmd", "insertFromPool");
        $this->tabs->addSubTab("copy_question",
            $this->lng->txt("cont_copy_question_from_pool"),
            $this->ctrl->getLinkTarget($this, "insert"));

        $this->tabs->activateSubTab($a_active);
    }
	
	/**
	 * Show form to insert a question
	 */
	public function insert()
	{
        $this->setInsertTabs("new_question");
        $this->initInsertForm();
        $this->tpl->setContent($this->form_gui->getHTML());
	}

    /**
     * Initialize the insert form
     */
	protected function initInsertForm()
    {
        $this->form_gui = new ilPropertyFormGUI();
        $this->form_gui->setFormAction($this->ctrl->getFormAction($this));
        $this->form_gui->setTitle($this->lng->txt("cont_ed_insert_pcqst"));

        // Select Question Type
        $options = $this->plugin->getAvailableQuestionTypeOptions();
        $qtype_input = new ilSelectInputGUI($this->lng->txt("cont_question_type"), "question_type");
        $qtype_input->setOptions($options);
        $qtype_input->setRequired(true);
        $this->form_gui->addItem($qtype_input);

        // additional content editor
        if (ilObjAssessmentFolder::isAdditionalQuestionContentEditingModePageObjectEnabled())
        {
            $ri = new ilRadioGroupInputGUI($this->lng->txt("tst_add_quest_cont_edit_mode"), "add_quest_cont_edit_mode");

            $ri->addOption(new ilRadioOption(
                $this->lng->txt('tst_add_quest_cont_edit_mode_default'),
                assQuestion::ADDITIONAL_CONTENT_EDITING_MODE_RTE
            ));

            $ri->addOption(new ilRadioOption(
                $this->lng->txt('tst_add_quest_cont_edit_mode_page_object'),
                assQuestion::ADDITIONAL_CONTENT_EDITING_MODE_IPE
            ));

            $ri->setValue(assQuestion::ADDITIONAL_CONTENT_EDITING_MODE_RTE);

            $this->form_gui->addItem($ri);
        }
        else
        {
            $hi = new ilHiddenInputGUI("question_content_editing_type");
            $hi->setValue(assQuestion::ADDITIONAL_CONTENT_EDITING_MODE_RTE);
            $this->form_gui->addItem($hi);
        }

        $this->form_gui->addCommandButton("create", $this->lng->txt("save"));
        $this->form_gui->addCommandButton("cancel", $this->lng->txt("cancel"));
    }


	/**
	 * Save a new question of the selected type
	 */
	public function create()
	{
		$this->initInsertForm();
		if ($this->form_gui->checkInput())
        {
            $question_type = $this->form_gui->getInput('question_type');
            $add_quest_cont_edit_mode = $this->form_gui->getInput('add_quest_cont_edit_mode');

            $q_gui = assQuestionGUI::_getQuestionGUI($question_type);
            require_once 'Modules/TestQuestionPool/exceptions/class.ilTestQuestionPoolException.php';
            try {
                $q_gui->object->setAdditionalContentEditingMode($add_quest_cont_edit_mode);
            } catch (ilTestQuestionPoolException $exception) {
                //Do nothing with exception
            }
            $q_gui->object->setDefaultNrOfTries(ilObjSAHSLearningModule::_getTries($this->plugin->getParentId()));
            // copage must be created
            // otherwise deleting the question brings an error
            $question_id = $q_gui->object->createNewQuestion(true);

            $properties = array('question_id' => $question_id);
            $this->createElement($properties);
            $this->editQuestion($properties['question_id']);
        }
		$this->form_gui->setValuesByPost();
		$this->tpl->setContent($this->form_gui->getHtml());
	}


    /**
     * Insert question from pool
     */
    public function insertFromPool()
    {
        if ($_SESSION["cont_qst_pool"] != ""
            && $this->access->checkAccess("write", "", $_SESSION["cont_qst_pool"])
            && ilObject::_lookupType(ilObject::_lookupObjId($_SESSION["cont_qst_pool"])) == "qpl")
        {
            $this->listPoolQuestions();
        }
        else
        {
            $this->poolSelection();
        }
    }

    /**
     * Pool selection
     */
    function poolSelection()
    {
        $this->setInsertTabs("copy_question");

        $this->ctrl->setParameter($this, "subCmd", "poolSelection");
        $exp = new ilPoolSelectorGUI($this, "insert");

        // filter
        $exp->setTypeWhiteList(array("root", "cat", "grp", "fold", "crs", "qpl"));
        $exp->setClickableTypes(array('qpl'));

        if (!$exp->handleCommand())
        {
            $this->tpl->setContent($exp->getHTML());
        }
    }

    /**
     * Select concrete question pool
     */
    function selectPool()
    {
        $_SESSION["cont_qst_pool"] = $_GET["pool_ref_id"];

        $this->ctrl->setParameter($this, "subCmd", "insertFromPool");
        $this->ctrl->redirect($this, "insert");
    }

    /**
     * List questions of pool
     */
    function listPoolQuestions()
    {
        $this->setInsertTabs("copy_question");

        ilUtil::sendInfo($this->lng->txt("cont_cp_question_diff_formats_info"));

        $this->ctrl->setParameter($this, "subCmd", "poolSelection");
        $button = ilLinkButton::getInstance();
        $button->setUrl($this->ctrl->getLinkTarget($this, 'insert'));
        $button->setCaption('cont_select_other_qpool');
        $this->toolbar->addButtonInstance($button);

        $this->plugin->includeClass('class.ilPCPluginQuestionSelectTableGUI.php');
        $this->ctrl->setParameter($this, "subCmd", "listPoolQuestions");
        $table_gui = new ilPCPluginQuestionSelectTableGUI($this, 'insert', $_SESSION["cont_qst_pool"]);

        $this->tpl->setContent($table_gui->getHTML());
    }

    /**
     * Copy question into page
     */
    function copyQuestion()
    {
        $question_id = $_GET["q_id"];

        /** @var assQuestion $question */
        $question = assQuestion::_instantiateQuestion($question_id);
        $duplicate_id = $question->copyObject(0, $question->getTitle());

        $properties['question_id'] = $duplicate_id;
        $this->createElement($properties);
        $this->editQuestion($properties['question_id']);
    }


	/**
	 * Edit
	 */
	public function edit()
	{
        $properties = $this->getProperties();
        $this->editQuestion($properties['question_id']);
	}

    /**
     * Show the question editor
     * @param int $question_id
     *
     * This redirects to the edit GUI of the plugin question through ilQuestionEditGUI
     *
     * @see ilPageObjectGUI::executeCommand()
     * @see ilQuestionEditGUI::executeCommand()
     */
	protected function editQuestion($question_id)
    {
        $this->ctrl->setParameterByClass("ilQuestionEditGUI", "q_id", $question_id);
        $this->ctrl->redirectByClass(array($this->plugin->getPageClass()."GUI", "ilQuestionEditGUI"), "editQuestion");
    }

	/**
	 * Cancel
	 */
	public function cancel()
	{
		$this->returnToParent();
	}


	/**
	 * Get HTML for element
	 *
	 * @param string    page mode (edit, presentation, print, preview, offline)
	 * @return string   html code
	 */
	public function getElementHTML($a_mode, array $a_properties, $a_plugin_version)
	{
		$display = array_merge($a_properties, $this->getPageInfo());

        $html = '';
		if ($a_properties['question_id'] > 0)
        {
            $this->plugin->includeClass('class.ilPCPluginQuestionPresentationGUI.php');
            $pres_gui = new ilPCPluginQuestionPresentationGUI();
            $pres_gui->setPlugin($this->plugin);
            $pres_gui->setPresentatioMode($a_mode);
            $pres_gui->setPageId($this->plugin->getPageId());
            $pres_gui->setQuestionId($a_properties['question_id']);
            $pres_gui->init();
            $html .= $pres_gui->getHTML();
        }

        // show properties stores in the page
        $html .=  '<pre>' . print_r($display, true) ;
        $html .= '</pre>';


        return $html;
	}



	/**
	 * Get information about the page that embeds the component
	 * @return	array	key => value
	 */
	public function getPageInfo()
	{
		return array(
			'page_id' => $this->plugin->getPageId(),
			'parent_id' => $this->plugin->getParentId(),
			'parent_type' => $this->plugin->getParentType()
		);
	}
}

?>