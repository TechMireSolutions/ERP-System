<style>
    /* Custom styles matching ERP if needed, but trying to use bootstrap classes first */
    .zke-card {
        background: #fff;
        border-radius: 5px;
        box-shadow: 0 1px 15px rgba(0, 0, 0, .04), 0 1px 6px rgba(0, 0, 0, .04);
    }
</style>

<div class="row">
    <div class="col-md-6">
        <div class="box box-block bg-white">
            <h2>Attendance System Integration</h2>
            <p class="text-muted">Connect to your ZKTeco Device to sync logs.</p>

            <!-- Step 1: Connection -->
            <div id="connection-section">
                <div class="form-group">
                    <label for="device_ip">Device IP Address</label>
                    <input type="text" class="form-control" id="device_ip" name="device_ip"
                        placeholder="e.g., 192.168.1.201" value="192.168.0.194" required>
                </div>
                <button type="button" id="btn-connect" class="btn btn-primary btn-block"><i class="fa fa-link"></i>
                    Connect & Sync</button>
                <div id="connection-status" class="mt-2 text-center font-weight-bold"></div>
            </div>

        </div>
    </div>

    <!-- Step 2: Date Selection (Hidden initially) -->
    <div class="col-md-6" id="date-section" style="display: none;">
        <div class="box box-block bg-white">
            <h3>Download Reports</h3>
            <p>Select date range and user to export Excel report.</p>

            <form action="<?php echo site_url('zke/export'); ?>" method="GET">
                <div class="form-group">
                    <label for="user_id">Select User</label>
                    <select class="form-control" id="user_id" name="user_id">
                        <option value="">All Users</option>
                        <option disabled>Loading users...</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-block"><i class="fa fa-download"></i> Download
                    Report</button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Check if we already have data by trying to fetch users silently? 
        // Or just let user connect again.

        document.getElementById('btn-connect').addEventListener('click', function () {
            var ip = document.getElementById('device_ip').value;
            var statusDiv = document.getElementById('connection-status');
            var btn = document.getElementById('btn-connect');
            var userSelect = document.getElementById('user_id');

            if (!ip) {
                statusDiv.innerHTML = '<span class="text-danger">Please enter an IP address.</span>';
                return;
            }

            // UI Loading State
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Connecting & Syncing...';
            statusDiv.innerHTML = 'Connecting to device...';

            // AJAX Request
            // Use CodeIgniter site_url
            fetch('<?php echo site_url("zke/connect"); ?>?ip=' + ip)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusDiv.innerHTML = '<span class="text-success">✅ ' + data.message + ' Loading users...</span>';

                        // Fetch Users from Cache
                        fetch('<?php echo site_url("zke/get_users"); ?>')
                            .then(response => response.json())
                            .then(userData => {
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fa fa-link"></i> Connect & Sync';

                                if (userData.success) {
                                    // Populate Dropdown
                                    userSelect.innerHTML = '<option value="">All Users</option>';
                                    userData.users.forEach(user => {
                                        var option = document.createElement('option');
                                        option.value = user.userid; // Use userid as value
                                        option.text = user.name + ' (ID: ' + user.userid + ')';
                                        userSelect.appendChild(option);
                                    });

                                    statusDiv.innerHTML = '<span class="text-success">✅ Data Synced & Ready!</span>';

                                    // Show Date Section
                                    document.getElementById('date-section').style.display = 'block';
                                } else {
                                    statusDiv.innerHTML = '<span class="text-warning">⚠️ Synced, but failed to load users: ' + userData.message + '</span>';
                                    document.getElementById('date-section').style.display = 'block';
                                }
                            });
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa fa-link"></i> Connect & Sync';
                        statusDiv.innerHTML = '<span class="text-danger">❌ ' + data.message + '</span>';
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa fa-link"></i> Connect & Sync';
                    statusDiv.innerHTML = '<span class="text-danger">❌ Error: ' + error + '</span>';
                });
        });
    });
</script>