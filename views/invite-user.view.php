<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" <?php echo applyThemeToHTML(); ?>>
<?php
require_once __DIR__ . '/../lang/Languaje.php';
$lang = Language::autoDetect();
?>
<head>
	<?php $title= "Invite Users - Pin9"; ?>
	<?php require 'head.php'; ?>
	
	<!-- Theme Switcher JS -->
	<script src="js/theme-switcher.js"></script>
	
	<style>
	/* Estilos específicos para invite-user.view.php usando CSS variables */
	.invite-user-container {
		background-color: var(--bg-card);
		border: 1px solid var(--border-color);
		border-radius: 15px;
		padding: 30px;
		margin: 0 auto;
		box-shadow: 0 4px 16px var(--shadow-light);
		transition: all var(--transition-speed) var(--transition-ease);
		max-width: 1200px;
	}
	
	.invite-user-title {
		color: var(--text-primary);
		font-size: 2rem;
		font-weight: 700;
		margin-bottom: 25px;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 15px;
	}
	
	.invite-user-title i {
		color: var(--primary-color);
	}
	
	.card {
		background-color: var(--bg-card);
		border: 1px solid var(--border-color);
		border-radius: 15px;
		margin-bottom: 20px;
	}
	
	.card-header {
		background-color: var(--bg-secondary);
		border-bottom: 1px solid var(--border-color);
		color: var(--text-primary);
		font-weight: 600;
	}
	
	.card-body {
		color: var(--text-primary);
	}
	
	.form-control {
		background-color: var(--bg-secondary);
		border: 1px solid var(--border-color);
		color: var(--text-primary);
		transition: all var(--transition-speed) var(--transition-ease);
	}
	
	.form-control:focus {
		background-color: var(--bg-secondary);
		border-color: var(--primary-color);
		color: var(--text-primary);
		box-shadow: 0 0 0 0.2rem var(--primary-color-alpha);
	}
	
	.form-control::placeholder {
		color: var(--text-muted);
	}
	
	.form-label {
		color: var(--text-primary);
		font-weight: 600;
		margin-bottom: 8px;
	}
	
	.btn {
		border-radius: 25px;
		padding: 12px 25px;
		font-weight: 600;
		transition: all var(--transition-speed) var(--transition-ease);
	}
	
	.btn:hover {
		transform: translateY(-1px);
		box-shadow: 0 4px 12px var(--shadow-medium);
	}
	
	.alert {
		border-radius: 10px;
		border: none;
		padding: 15px 20px;
		margin-bottom: 20px;
	}
	
	.alert-success {
		background-color: var(--success-color-alpha);
		color: var(--success-color);
	}
	
	.alert-danger {
		background-color: var(--danger-color-alpha);
		color: var(--danger-color);
	}
	
	.badge {
		border-radius: 15px;
		padding: 6px 12px;
		font-weight: 600;
	}
	
	.table {
		background-color: var(--bg-card);
		border: 1px solid var(--border-color);
		border-radius: 10px;
		overflow: hidden;
	}
	
	.table thead th {
		background-color: var(--bg-secondary);
		border-color: var(--border-color);
		color: var(--text-primary);
		font-weight: 600;
	}
	
	.table tbody td {
		border-color: var(--border-color);
		color: var(--text-primary);
		background-color: var(--bg-card);
	}
	
	.table tbody tr:hover {
		background-color: var(--bg-secondary);
	}
	
	/* Responsive */
	@media (max-width: 768px) {
		.invite-user-container {
			padding: 20px;
			margin: 10px;
		}
		
		.invite-user-title {
			font-size: 1.5rem;
		}
	}
	</style>
</head>

<body class="bg">
<?php require_once __DIR__ . '/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<!-- Page Content -->
<div class="container mt-4">
	<div class="invite-user-container">
		<h2 class="invite-user-title">
			<i class="fas fa-user-plus"></i> Invite Users to <?php echo htmlspecialchars($company['company_name']); ?>
		</h2>
			
			<!-- Company Info -->
			<div class="card mb-4">
				<div class="card-header">
					<h5><i class="fas fa-building"></i> Company Information</h5>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-6">
							<p><strong>Company:</strong> <?php echo htmlspecialchars($company['company_name']); ?></p>
							<p><strong>Email:</strong> <?php echo htmlspecialchars($company['company_email']); ?></p>
							<p><strong>Plan:</strong> <?php echo ucfirst($company['subscription_plan']); ?></p>
						</div>
						<div class="col-md-6">
							<p><strong>Max Users:</strong> <?php echo $company['max_users']; ?></p>
							<p><strong>Status:</strong> 
								<span class="badge badge-<?php echo $company['subscription_status'] === 'active' ? 'success' : 'warning'; ?>">
									<?php echo ucfirst($company['subscription_status']); ?>
								</span>
							</p>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Invite Form -->
			<div class="card mb-4">
				<div class="card-header">
					<h5><i class="fas fa-envelope"></i> Send Invitation</h5>
				</div>
				<div class="card-body">
					<form method="POST">
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label for="email">Email Address *</label>
									<input type="email" class="form-control" id="email" name="email" required>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label for="role">Role *</label>
									<select class="form-control" id="role" name="role" required>
										<option value="user">User</option>
										<option value="admin">Admin</option>
									</select>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="message">Personal Message (Optional)</label>
							<textarea class="form-control" id="message" name="message" rows="3" placeholder="Add a personal message to the invitation..."></textarea>
						</div>
						<button type="submit" class="btn btn-primary">
							<i class="fas fa-paper-plane"></i> Send Invitation
						</button>
					</form>
					
					<?php if(!empty($errors)): ?>
						<div class="alert alert-danger mt-3">
							<ul class="mb-0">
								<?php echo $errors; ?>
							</ul>
						</div>
					<?php endif; ?>
					
					<?php if(!empty($success)): ?>
						<div class="alert alert-success mt-3">
							<?php echo $success; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			
			<!-- Pending Invitations -->
			<div class="card">
				<div class="card-header">
					<h5><i class="fas fa-clock"></i> Pending Invitations</h5>
				</div>
				<div class="card-body">
					<?php if (empty($invitations)): ?>
						<p class="text-muted">No pending invitations.</p>
					<?php else: ?>
						<div class="table-responsive">
							<table class="table table-striped">
								<thead>
									<tr>
										<th>Email</th>
										<th>Role</th>
										<th>Invited By</th>
										<th>Status</th>
										<th>Sent Date</th>
										<th>Expires</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach($invitations as $invitation): ?>
										<tr>
											<td><?php echo htmlspecialchars($invitation['email']); ?></td>
											<td>
												<span class="badge badge-<?php echo $invitation['role'] === 'admin' ? 'danger' : 'info'; ?>">
													<?php echo ucfirst($invitation['role']); ?>
												</span>
											</td>
											<td>
												<?php if ($invitation['first_name']): ?>
													<?php echo htmlspecialchars($invitation['first_name'] . ' ' . $invitation['last_name']); ?>
												<?php else: ?>
													<span class="text-muted">Unknown</span>
												<?php endif; ?>
											</td>
											<td>
												<span class="badge badge-<?php 
													echo $invitation['status'] === 'pending' ? 'warning' : 
														($invitation['status'] === 'accepted' ? 'success' : 'secondary'); 
												?>">
													<?php echo ucfirst($invitation['status']); ?>
												</span>
											</td>
											<td><?php echo date('M j, Y', strtotime($invitation['created_at'])); ?></td>
											<td><?php echo date('M j, Y', strtotime($invitation['expires_at'])); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- --------------------- JS SCRIPTS JQUERY + POPPER + BOOTSTRAP ------------------------- -->
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

<script>
$(document).ready(function() {
    // Inicializar theme switcher
    if (typeof ThemeSwitcher !== 'undefined') {
        const themeSwitcher = new ThemeSwitcher();
        themeSwitcher.init();
    }
});
</script>

</body>
</html> 