<?php
/**
 * Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv3, see docs/LICENSE
 */


/**
 * Table to select plugin questions for copying into learning modules
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 *
 * @ingroup ModulesTestQuestionPool
 */
class ilPCPluginQuestionSelectTableGUI extends ilTable2GUI
{
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;

	/**
	 * @var ilAccessHandler
	 */
	protected $access;

	/** @var ilPCPluginQuestionPlugin */
	protected $plugin;

	/**
	 * @var int
	 */
	protected $pool_ref_id;

	/**
	 * @var int
	 */
	protected $pool_obj_id;

	/**
	 * constructor.
	 * @param ilPCPluginQuestionPluginGUI $a_parent_obj
	 * @param string $a_parent_cmd
	 * @param string $a_pool_ref_id
	 */
	public function __construct($a_parent_obj, $a_parent_cmd, $a_pool_ref_id)
	{
		global $DIC;

		$this->ctrl = $DIC->ctrl();
		$this->access = $DIC->access();
		$this->plugin = $a_parent_obj->getPlugin();

		$this->setId("cont_qpl");
		$this->pool_ref_id = $a_pool_ref_id;
		$this->pool_obj_id = ilObject::_lookupObjId($a_pool_ref_id);
		
		parent::__construct($a_parent_obj, $a_parent_cmd);
		
		$this->setTitle(ilObject::_lookupTitle($this->pool_obj_id));

		$this->setFormName('sa_quest_browser');

		$this->addColumn($this->lng->txt("title"),'title', '');
		$this->addColumn($this->lng->txt("cont_question_type"),'ttype', '');
		$this->addColumn($this->lng->txt("actions"),'', '');

		$this->setRowTemplate("tpl.copy_quest_row.html", $this->plugin->getDirectory());

		$this->setFormAction($this->ctrl->getFormAction($a_parent_obj, $a_parent_cmd));
		$this->setDefaultOrderField("title");
		$this->setDefaultOrderDirection("asc");
		
		$this->initFilter();
		
		$this->getQuestions();
	}

	/**
	 * Get questions
	 */
	function getQuestions()
	{
		global $DIC;

		$types = $this->plugin->getAvailableQuestionTypeOptions();
		$questions = array();
		if ($this->access->checkAccess("read", "", $this->pool_ref_id))
		{
			$questionList = new ilAssQuestionList($DIC->database(), $DIC->language(), $DIC["ilPluginAdmin"]);
			$questionList->setParentObjId($this->pool_obj_id);
			$questionList->load();
			
			$data = $questionList->getQuestionDataArray();

			$questions = array();
			foreach ($data as $question)
			{
				// list only questions of allowed types
				if (isset($types[$question['type_tag']]))
				{
					$questions[] = $question;
				}
			}
		}
		$this->setData($questions);
	}

	/**
	 * Fill row 
	 *
	 * @param array $a_set data array
	 */
	public function fillRow($a_set)
	{
		// action: copy
		$this->ctrl->setParameter($this->parent_obj, "q_id", $a_set["question_id"]);
		$this->ctrl->setParameter($this->parent_obj, "subCmd", "copyQuestion");
		$this->tpl->setCurrentBlock("cmd");
		$this->tpl->setVariable("HREF_CMD",
			$this->ctrl->getLinkTarget($this->parent_obj, $this->parent_cmd));
		$this->tpl->setVariable("TXT_CMD",
			$this->lng->txt("cont_copy_question_into_page"));
		$this->tpl->parseCurrentBlock();
		$this->ctrl->setParameter($this->parent_obj, "subCmd", "listPoolQuestions");
		
		// properties
		$this->tpl->setVariable("TITLE", $a_set["title"]);
		$this->tpl->setVariable("TYPE",
			assQuestion::_getQuestionTypeName($a_set["type_tag"]));
	}
}
?>