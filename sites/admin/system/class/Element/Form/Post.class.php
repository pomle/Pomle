<?
namespace Element\Form;

class Post extends \Element\Common\Root
{
	public function __construct(\Post $Post)
	{
		$size = 40;
		$this->Table = \Element\Table::inputs()
			->addRow(_('Aktiv'), \Element\Input::checkbox('isPublished', $Post->isPublished))
			->addRow(_('Datum'), \Element\Input::text('timePublished', \Format::timestamp($Post->timePublished ?: time(), true))->size($size))
			->addRow(_('Titel'), \Element\Input::text('title', $Post->title)->size($size))
			->addRow(_('URI'), \Element\Input::text('uri', $Post->uri)->size($size))
			->addRow(_('MediaID'), \Element\Input::text('previewMediaID', $Post->previewMediaID)->size(5))
			;
	}

	public function __toString()
	{
		ob_start();
		?>
		<fieldset>
			<legend><? echo \Element\Tag::legend('book', _('Post')); ?></legend>
			<? echo $this->Table; ?>
		</fieldset>
		<?
		return ob_get_clean();
	}
}