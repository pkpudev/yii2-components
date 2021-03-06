<?php

namespace pkpudev\components\widgets;

use kartik\select2\Select2;
use pkpudev\components\data\QueryCollection;
use pkpudev\components\web\ViewBehavior;
use yii\base\Widget;
use yii\db\ActiveRecordInterface;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class ProgramTreeview extends Widget
{
	/**
	 * @var string $id Selector ID for widget
	 */
	public $id = 'selectProgram';
	/**
	 * @var ActiveRecordInterface $modelProgram
	 */
	public $modelProgram;
	/**
	 * @var ActiveRecordInterface $model
	 */
	public $model;
	/**
	 * @var string $attribute
	 */
	public $attribute;
	/**
	 * @var string $colspanText
	 */
	public $colspanText = 'col-sm-10';
	/**
	 * @var QueryCollection $dataProvider
	 */
	public $dataProvider = null;
	/**
	 * @var bool $isRamadhan
	 */
	public $isRamadhan = false;
	/**
	 * @var bool isHorizontal
	 */
	public $isHorizontal = true;
	/**
	 * @var string|mixed $jsCallback
	 */
	public $jsCallback;
	/**
	 * @var bool $readOnly
	 */
	public $readOnly = false;
	/**
	 * @var bool $useHierarchy
	 */
	public $useHierarchy = true;
	/**
	 * @var bool $multiple
	 */
	public $multiple = false;

	/**
	 * @var string $fullHierarchy
	 */
	protected $fullHierarchy;
	/**
	 * @var ActiveRecordInterface[] $data
	 */
	protected $data;

	public function init()
	{
		parent::init();

		$validDataProvider = ($this->dataProvider instanceof QueryCollection);
		$validModel = ($this->modelProgram instanceof ActiveRecordInterface) &&
			strpos(get_class($this->modelProgram), 'Program');

		if (!$validDataProvider && !$validModel) {
			throw new \yii\base\InvalidConfigException("Data Source is invalid");
		}

		$this->view->attachBehavior('addfun', new ViewBehavior);
		$this->data = $this->getRecords();
	}

	public function run()
	{
		$this->createDropdown();
		$this->registerJs();
	}

	protected function getRecords()
	{
		if (isset($this->dataProvider)) return $this->dataProvider;

		$modelProgram = $this->modelProgram;
		$programs = $modelProgram::find()->activeNow();
		if ($this->isRamadhan) {
			$programs->ramadhan();
		}

		return array_map(function($row) {
			return [
				'id'=>$row->id,
				'program_name'=>$row->program_name,
				'parent_name'=>$row->parent->program_name,
			];
		}, $programs->all());
	}

	protected function createDropdown()
	{
		$modelProgram = $this->modelProgram;
		if ($this->useHierarchy && !empty($this->model->{$this->attribute}) && !isset($this->dataProvider))
			if (null !== ($program = $modelProgram::findOne($this->model->{$this->attribute})))
				$this->fullHierarchy = $program->full_hierarchy;

		$readonly = $this->readOnly ? ['readonly'=>'readonly'] : [];
		$activeLabel = Html::activeLabel($this->model, $this->attribute, [
			'class'=>'control-label '.($this->isHorizontal?'col-md-2':null),
			'required'=>$this->model->isAttributeRequired($this->attribute),
		]);
		$dropdown = Select2::widget([
			'model'=>$this->model,
			'attribute'=>$this->attribute,
			'data' => ArrayHelper::map($this->data, 'id', 'program_name', 'parent_name'),
			'options' => ArrayHelper::merge($readonly, [
				'id' => $this->id,
				'prompt' => '--- Pilih Program ---',
				'multiple' => $this->multiple,
			]),
			'pluginOptions' => [
				'allowClear'=>true,
				'maximumSelectionLength'=>10,
			],
		]);
		$errorBlock = Html::error($this->model, $this->attribute, [
			'class'=>'help-block error'
		]);

		if ($this->isHorizontal) {
			echo $this->templateHorizontal($activeLabel, $dropdown, $errorBlock);
		} else {
			echo $this->templateVertical($activeLabel, $dropdown, $errorBlock);
		}
	}

	protected function templateHorizontal($activeLabel, $dropdown, $errorBlock)
	{
		?>
		<div class="form-group">
			<?= $activeLabel ?>
			<div class="<?=$this->colspanText?>">
				<?= $dropdown ?>
				<span class="help-block treeProgram"><?=$this->fullHierarchy;?></span>
				<?= $errorBlock ?>
			</div>
		</div>
		<?php
	}

	protected function templateVertical($activeLabel, $dropdown, $errorBlock)
	{
		?>
		<div class="form-group">
			<?= $activeLabel ?>
			<?= $dropdown ?>
			<span class="help-block treeProgram"><?=$this->fullHierarchy;?></span>
			<?= $errorBlock ?>
		</div>
		<?php
	}

	protected function registerJs()
	{
		$script = (string)$this->jsCallback;
		$this->view->addJsFuncReady($script);
	}
}