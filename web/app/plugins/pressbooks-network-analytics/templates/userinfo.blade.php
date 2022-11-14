<div class="wrap">

	@if ( ! empty( $info['first_name'] ) || ! empty( $info['last_name'] ) )
		@php
			$fullname = trim( "{$info['first_name']} {$info['last_name']}" );
			$fullname = ucwords( strtolower( $fullname ) );
		@endphp
	@endif
	<div class="postbox">
		<div class="inside">
			<h1>{{ __( 'User Info', 'pressbooks-network-analytics') }}</h1>
			<ul>
				<li>{{ __( 'Username', 'pressbooks-network-analytics' ) }}: {{ $info['username'] }}</li>
				@isset ($fullname)
					<li>{{ __( 'Name', 'pressbooks-network-analytics' ) }}: {{$fullname }}</li>@endisset
				<li>{{ __( 'Account creation', 'pressbooks-network-analytics' ) }}: {{ $info['created_on'] ?? 'N/A' }}</li>
				<li>{{ __( 'Last log in', 'pressbooks-network-analytics' ) }}: {{ $info['last_login'] ?? 'N/A' }}</li>
				<li>{{ __( 'Belongs to # of books', 'pressbooks-network-analytics' ) }}: {{ $info['total_books'] }}</li>
				<li>{{ __( 'Revisions', 'pressbooks-network-analytics' ) }}: {{ $info['total_revision'] }}</li>
			</ul>
		</div>
	</div>
	<a class="button" href="{!! self_admin_url( 'user-edit.php?user_id=' . $info['id'] ) !!}">{{ __( 'Edit This User', 'pressbooks-network-analytics' ) }}</a>
	<a class="button" href="{!! self_admin_url( 'admin.php?page=pb_network_analytics_userlist' ) !!}">{{ __( 'Back To User List', 'pressbooks-network-analytics' ) }}</a>
	@php
		$required_order = ['administrator','editor','author','contributor','subscriber'];
		$required_order = array_diff($required_order,array_diff($required_order,array_keys($info['books']))); // Compare only existent roles
		$roles = array_replace(array_flip($required_order), $info['books'])
	@endphp
	@foreach ($roles as $role => $books)
		<h2>{{ __( 'Books as', 'pressbooks-network-analytics' ) }} {{ ucfirst($role !== 'contributor' ? $role : 'Collaborator') }}: {{ count($books) }}</h2>
		<ol>
			@foreach ($books as $book)
				<li>
					<b>{{ $book['blogname'] }}</b><br>
					<a href="{!! esc_attr($book['siteurl']) !!}">{{ $book['siteurl'] }}</a><br>
					{{ __( 'Revisions made', 'pressbooks-network-analytics' ) }}: {{ $book['revisions'] }}<br>
					{{ __( 'Date of last revision', 'pressbooks-network-analytics' ) }}
					: {{ !empty($book['last_revision']) ? $book['last_revision'] : __( 'N/A', 'pressbooks-network-analytics' ) }}
				</li>
			@endforeach
		</ol>
	@endforeach

</div>
