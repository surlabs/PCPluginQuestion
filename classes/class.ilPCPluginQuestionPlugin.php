<?php
/**
 * Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv3, see docs/LICENSE
 */
 
/**
 * Plugin question Page Component plugin
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 */
class ilPCPluginQuestionPlugin extends ilPageComponentPlugin
{
	/**
	 * Get plugin name 
	 *
	 * @return string
	 */
	function getPluginName()
	{
		return "PCPluginQuestion";
	}


	/**
	 * Check if parent type is valid
	 *
	 * @return string
	 */
	function isValidParentType($a_parent_type)
	{
		return $a_parent_type == 'lm';
	}


	/**
	 * Get the GUI class of the current page
	 */
	public function getPageClass()
	{
		switch ($this->getParentType())
		{
			case 'lm':
				return 'ilLMPage';

			default:
				return 'ilPageObject';
		}
	}

	public function getParentRefId()
	{
		return $_GET['ref_id'];
	}

    /**
     * Get the select options for available question types
	 * @return array	type => name
     */
	public function getAvailableQuestionTypeOptions()
	{
		global $DIC;

		/** @var ilPluginAdmin $ilPluginAdmin */
        $ilPluginAdmin = $DIC['ilPluginAdmin'];

		$types = array();
        $pl_names = $ilPluginAdmin->getActivePluginsForSlot(IL_COMP_MODULE, "TestQuestionPool", "qst");
        foreach ($pl_names as $pl_name)
        {
        	/** @var ilQuestionsPlugin $pl */
            $pl = ilPlugin::getPluginObject(IL_COMP_MODULE, "TestQuestionPool", "qst", $pl_name);
            $types[$pl->getQuestionType()] = $pl->getQuestionTypeTranslation();
        }

        asort($types);
		return $types;
	}

	/**
	 * Handle an event
	 * @param string	$a_component
	 * @param string	$a_event
	 * @param mixed		$a_parameter
	 */
	public function handleEvent($a_component, $a_event, $a_parameter)
	{
		// todo: eventually delete user data
	}

	/**
	 * This function is called when the page content is cloned
	 * @param array 	$a_properties		properties saved in the page, (should be modified if neccessary)
	 * @param string	$a_plugin_version	plugin version of the properties
	 */
	public function onClone(&$a_properties, $a_plugin_version)
	{
		if ($question_id = $a_properties['question_id'])
		{
            $question = assQuestion::_instantiateQuestion($question_id);
            $clone_id = $question->duplicate();
            $a_properties['question_id'] = $clone_id;
		}
	}


	/**
	 * This function is called before the page content is deleted
	 * @param array 	$a_properties		properties saved in the page (will be deleted afterwards)
	 * @param string	$a_plugin_version	plugin version of the properties
	 */
	public function onDelete($a_properties, $a_plugin_version)
	{
		if ($question_id = $a_properties['question_id'])
		{
            $question = assQuestion::_instantiateQuestion($question_id);
            $question->delete($question_id);
		}
	}
}

?>
