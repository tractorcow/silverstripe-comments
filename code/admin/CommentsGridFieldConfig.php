<?php

class CommentsGridFieldConfig extends GridFieldConfig_RecordEditor {
	
	public function __construct($itemsPerPage = 25) {
		parent::__construct($itemsPerPage);

		// $this->addComponent(new GridFieldExportButton());

		$this->addComponent(new CommentsGridFieldAction());

		// Format column
		$columns = $this->getComponentByType('GridFieldDataColumns');
		$columns->setFieldFormatting(array(
			'ParentTitle' => function($value, &$item) {
				return sprintf(
					'<a href="%s" class="cms-panel-link external-link action" target="_blank">%s</a>',
					Convert::raw2att($item->Link()),
					$item->obj('ParentTitle')->forTemplate()
				);
			}
		));

		// Add bulk option
		$manager = new GridFieldBulkManager();
		$this->addSpamAction($manager);
		$this->addApproveAction($manager);

		$manager->removeBulkAction('bulkEdit');
		$manager->removeBulkAction('unLink');

		$this->addComponent($manager);
	}

	protected function addSpamAction($manager) {
		$manager->addBulkAction(
			'markAsSpam',
			_t('CommentsGridFieldConfig.MARKASSPAM', 'Mark as spam'),
			'CommentsGridFieldBulkAction_Handlers',
			array(
				'isAjax' => true,
				'icon' => 'cross',
				'isDestructive' => false
			)
		);
	}

	protected function addApproveAction($manager) {
		$manager->addBulkAction(
			'markApproved',
			_t('CommentsGridFieldConfig.APPROVE', 'Approve'),
			'CommentsGridFieldBulkAction_Handlers',
			array(
				'isAjax' => true,
				'icon' => 'cross',
				'isDestructive' => false
			)
		);
	}
}