<?php /** @var $data array|object */ ?>
<div>
	<div class="page-header">
		<h1>Form Example</h1>
	</div>

	<div class="well">
		<form class="form-signin" method="post" action="/restAccept/index">
			<h2 class="form-signin-heading">Please sign in</h2>
			<label for="inputEmail" class="sr-only">Email address</label>
			<input name="inputEmail" type="email" id="inputEmail" class="form-control" placeholder="Email address" required=""
			       autofocus="">
			<label for="inputPassword" class="sr-only">Password</label>
			<input name="inputPassword" type="password" id="inputPassword" class="form-control" placeholder="Password" required="">
			<div class="checkbox">
				<label>
					<input name="rememberme" type="checkbox"> Remember me
				</label>
			</div>
			<div class="checkbox">
				<label>
					<input name="lalala" value="1" type="checkbox"> Remember me
				</label>
			</div>
			<button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
		</form>
	</div>
</div>