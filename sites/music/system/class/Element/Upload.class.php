<?
namespace Element;

class Upload
{
	public function __construct($url)
	{
		$this->url = $url;
	}

	public function __toString()
	{
		ob_start();

		?>
		<form action="<? echo $this->url; ?>" method="post">
			<div class="dropUpload">

				<div class="dropArea">
					<span><? echo htmlspecialchars(_("Drop Files Here")); ?></span>
				</div>

				<div class="queue">
					<div class="items">

					</div>
				</div>

				<div class="messages">

				</div>

			</div>
		</form>
		<?

		return ob_get_clean();
	}
}