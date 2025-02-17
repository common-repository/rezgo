<div class="container-fluid">
	<div class="jumbotron">
		<h2 id="rezgo-error-head">There was an error with your request <i class="fa fa-exclamation-triangle"></i></h2>
		<div class="rezgo-page-content">
			<p class="lead">
				We are sorry for any inconvenience, but the system encountered an error while handling your request.<br />
				<br />
				Our staff have been alerted of this error automatically, you do not need to take any more steps to report this.<br />
				<br />
				please <a id="back" href="#">click here</a> to go back to your previous page.
			</p>
		</div>
	</div>
</div>

<script>
	jQuery(document).ready(function($){
		$("#back").on("click",function(e){
			e.preventDefault();

			parent.history.back();
		});
	});
</script>