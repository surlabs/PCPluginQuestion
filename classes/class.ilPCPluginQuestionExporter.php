<?php
/**
 * Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv3, see docs/LICENSE
 */

/**
 * Exporter class for the PCPluginQuestion Plugin
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 *
 * @ingroup ServicesCOPage
 */
class ilPCPluginQuestionExporter extends ilPageComponentPluginExporter
{
	public function init()
	{
	}

	/**
	 * Get head dependencies
	 *
	 * @param		string		entity
	 * @param		string		target release
	 * @param		array		ids
	 * @return		array		array of array with keys "component", entity", "ids"
	 */
	function getXmlExportHeadDependencies($a_entity, $a_target_release, $a_ids)
	{
		// collect the files to export
		$file_ids = array();
		foreach ($a_ids as $id)
		{
			$properties = self::getPCProperties($id);
			if (isset($properties['page_file']))
			{
				$file_ids[] = $properties['page_file'];
			}
		}

		// add the files as dependencies
		if (!empty(($file_ids)))
		{
			return array(
				array(
					"component" => "Modules/File",
					"entity" => "file",
					"ids" => $file_ids)
			);
		}

		return array();
	}


	/**
	 * Get xml representation
	 *
	 * @param	string		entity
	 * @param	string		schema version
	 * @param	string		id
	 * @return	string		xml string
	 */
	public function getXmlRepresentation($a_entity, $a_schema_version, $a_id)
	{
        if ($a_entity == "pgcp") {

            /** @var ilPCPluginQuestionPlugin $plugin */
            $plugin = ilPluginAdmin::getPluginObject(IL_COMP_SERVICE, 'COPage', 'pgcp', 'PCPluginQuestion');
            $properties = self::getPCProperties($a_id);

            //Get the question type
            $question_id = $properties['question_id'];
            $question_type = assQuestion::_getQuestionType($question_id);

            //Include main class
            assQuestion::_includeClass($question_type);

            //Create object and load data
            $question = new $question_type();
            $question->loadFromDb($question_id);

            //Get XML
            $xml = $question->toXML();
            return $xml;
        } else {
            return $this->ds->getXmlRepresentation($a_entity, $a_schema_version, $a_id, "", true, true);
        }
	}

	/**
	 * Get tail dependencies
	 *
	 * @param		string		entity
	 * @param		string		target release
	 * @param		array		ids
	 * @return		array		array of array with keys "component", entity", "ids"
	 */
	function getXmlExportTailDependencies($a_entity, $a_target_release, $a_ids)
	{
		return array();
	}

	/**
	 * Returns schema versions that the component can export to.
	 * ILIAS chooses the first one, that has min/max constraints which
	 * fit to the target release. Please put the newest on top. Example:
	 *
	 * 		return array (
	 *		"4.1.0" => array(
	 *			"namespace" => "http://www.ilias.de/Services/MetaData/md/4_1",
	 *			"xsd_file" => "ilias_md_4_1.xsd",
	 *			"min" => "4.1.0",
	 *			"max" => "")
	 *		);
	 *
	 *
	 * @return		array
	 */
	public function getValidSchemaVersions($a_entity)
	{
		return array(
			'5.3.0' => array(
				'namespace'    => 'http://www.ilias.de/',
				//'xsd_file'     => 'pctpc_5_3.xsd',
				'uses_dataset' => false,
				'min'          => '5.3.0',
				'max'          => ''
			)
		);
	}
}