
<!-- Stats Row (Hidden initially) -->
<div class="row row-md mb-1" id="live-stats-row" style="display: none;">
    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
        <div class="box box-block tile tile-2 bg-primary mb-2">
            <div class="t-icon right"><i class="fa fa-users"></i></div>
            <div class="t-content">
                <h1 class="mb-1" id="stat-total-users">0</h1>
                <h6 class="text-uppercase">Total Employees</h6>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
        <div class="box box-block tile tile-2 bg-success mb-2">
            <div class="t-icon right"><i class="fa fa-check-circle"></i></div>
            <div class="t-content">
                <h1 class="mb-1" id="stat-present">0</h1>
                <h6 class="text-uppercase">Present Today</h6>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
        <div class="box box-block tile tile-2 bg-warning mb-2">
            <div class="t-icon right"><i class="fa fa-clock-o"></i></div>
            <div class="t-content">
                <h1 class="mb-1" id="stat-last-punch">--:--</h1>
                <h6 class="text-uppercase">Last Activity</h6>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row row-md mb-1">
    <!-- Left Column: Controls (Integration) -->
    <div class="col-md-4">
        <!-- Connection Box -->
        <div class="box box-block bg-white mb-2">
            <h2><strong>Integration</strong></h2>
            <p class="text-muted small">Connect Device / Sync Logs</p>
            <div id="connection-section">
                <div class="form-group mb-1">
                    <label for="device_ip">Device IP</label>
                    <input type="text" class="form-control" id="device_ip" name="device_ip"
                        placeholder="192.168.1.201" value="192.168.0.194" required>
                </div>
                <!-- Late Calculation Settings -->
                <div class="form-group mb-1">
                    <label for="shift_start">Office Start Time <small class="text-muted">(for Late calc)</small></label>
                    <input type="time" class="form-control" id="shift_start" value="09:15">
                </div>
                
                <button type="button" id="btn-connect" class="btn btn-primary btn-block"><i class="fa fa-link"></i>
                    Connect & Sync</button>
                <div id="connection-status" class="mt-2 text-center font-weight-bold"></div>
            </div>
        </div>

        <!-- Report Download Box (Initially Hidden) -->
        <div class="box box-block bg-white" id="date-section" style="display: none;">
            <h2><strong>Reports</strong></h2>
            <form action="<?php echo site_url('zke/export'); ?>" method="GET">
                <div class="form-group mb-1">
                    <label>Filter User</label>
                    <select class="form-control" id="user_id" name="user_id">
                        <option value="">All Users</option>
                    </select>
                </div>
                <div class="form-group mb-1">
                    <label>Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                </div>
                <div class="form-group mb-1">
                    <label>End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" required>
                </div>
                <button type="submit" class="btn btn-outline-success btn-block"><i class="fa fa-download"></i> Export Excel</button>
            </form>
        </div>
    </div>

    <!-- Right Column: Live Table -->
    <div class="col-md-8">
        <div class="box box-block bg-white" id="live-table-box" style="display:none; min-height: 480px;">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="mb-0"><strong>Status Board</strong> <small class="text-muted navbar-text" style="font-size: 0.6em;"><?php echo date('D, d M'); ?></small></h2>
                </div>
                <div class="col-md-6 text-xs-right">
                    <!-- Controls in Header -->
                    <div class="form-inline float-xs-right">
                        <label class="custom-control custom-checkbox mr-1">
                            <input type="checkbox" class="custom-control-input" id="check-auto-refresh">
                            <span class="custom-control-indicator"></span>
                            <span class="custom-control-description small">Auto-Refresh</span>
                        </label>
                        <input type="text" class="form-control form-control-sm" id="input-search" placeholder="Search name..." style="width: 150px;">
                    </div>
                </div>
            </div>

            <div class="table-responsive mt-1">
                <table class="table table-hover table-striped" id="table-whos-in" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>In Time</th>
                            <th>Status/Out</th>
                            <th>State</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <!-- Placeholder when not synced -->
        <div class="box box-block bg-white text-center" id="sync-placeholder" style="min-height: 480px; display: flex; align-items: center; justify-content: center;">
            <div style="opacity: 0.5;">
                <i class="fa fa-cloud-download fa-5x mb-2"></i>
                <h4 class="">Click "Connect & Sync" to view Dashboard</h4>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let refreshInterval = null;

        // --- 1. Auto Refresh Logic ---
        document.getElementById('check-auto-refresh').addEventListener('change', function(e) {
            if(e.target.checked) {
                if(!refreshInterval) {
                    refreshInterval = setInterval(loadLiveStats, 30000); // 30 seconds
                    // show toast or small indicator?
                    console.log('Auto-refresh enabled');
                }
            } else {
                if(refreshInterval) {
                    clearInterval(refreshInterval);
                    refreshInterval = null;
                    console.log('Auto-refresh disabled');
                }
            }
        });

        // --- 2. Search Logic ---
        document.getElementById('input-search').addEventListener('keyup', function(e) {
            const term = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#table-whos-in tbody tr');
            rows.forEach(row => {
                const nameCell = row.querySelector('td:first-child');
                if(nameCell) {
                    const name = nameCell.innerText.toLowerCase();
                    if(name.indexOf(term) > -1) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });

        function loadLiveStats() {
            fetch('<?php echo site_url("zke/get_live_stats"); ?>')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Show Dashboard Elements
                        document.getElementById('live-stats-row').style.display = ''; // default
                        document.getElementById('live-stats-row').style.removeProperty('display');

                        document.getElementById('live-table-box').style.display = 'block';
                        document.getElementById('sync-placeholder').style.display = 'none';

                        // Update Cards
                        if(document.getElementById('stat-total-users')) document.getElementById('stat-total-users').innerText = data.stats.total_users;
                        if(document.getElementById('stat-present')) document.getElementById('stat-present').innerText = data.stats.present_count;
                        
                        if (data.stats.latest_punch) {
                            document.getElementById('stat-last-punch').innerText = data.stats.latest_punch.time;
                        } else {
                            document.getElementById('stat-last-punch').innerText = "--:--";
                        }

                        // Update Table
                        const tbody = document.querySelector('#table-whos-in tbody');
                        tbody.innerHTML = '';
                        const shiftStartVal = document.getElementById('shift_start').value; // e.g. "09:15"

                        if (data.stats.present_list.length > 0) {
                            data.stats.present_list.forEach(user => {
                                const tr = document.createElement('tr');
                                // Determine working status
                                const isWorking = (user.first_punch !== user.last_punch);
                                const statusClass = isWorking ? 'tag tag-success' : 'tag tag-info';
                                const statusText = isWorking ? 'Working' : 'Just Arrived';
                                
                                const timeInDate = new Date(user.first_punch);
                                // Format time 12h
                                const timeIn = timeInDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
                                const timeOut = new Date(user.last_punch).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });

                                // Late Calculation
                                let lateBadge = '';
                                // Compare HH:MM 24h format strings for simplicity
                                const punchTimeStr = timeInDate.toTimeString().substring(0,5); // "09:23"
                                if(punchTimeStr > shiftStartVal) {
                                    lateBadge = '<span class="tag tag-danger">Late</span>';
                                }

                                tr.innerHTML = `
                                    <td class="font-weight-bold">${user.name}</td>
                                    <td>${timeIn} ${lateBadge}</td>
                                    <td>${timeOut}</td>
                                    <td><span class="${statusClass}">${statusText}</span></td>
                                `;
                                tbody.appendChild(tr);
                            });

                            // Re-apply search filter if exists
                            const searchTerm = document.getElementById('input-search').value;
                            if(searchTerm) {
                                document.getElementById('input-search').dispatchEvent(new Event('keyup'));
                            }

                        } else {
                            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No attendance records found for today yet.</td></tr>';
                        }
                    }
                })
                .catch(err => console.log('Stats error:', err));
        }

        // Initial Load
        loadLiveStats();

        // Connection Logic
        document.getElementById('btn-connect').addEventListener('click', function () {
            var ip = document.getElementById('device_ip').value;
            var statusDiv = document.getElementById('connection-status');
            var btn = document.getElementById('btn-connect');
            var userSelect = document.getElementById('user_id');

            if (!ip) {
                statusDiv.innerHTML = '<span class="text-danger">Please enter an IP address.</span>';
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Connecting...';
            statusDiv.innerHTML = 'Connecting...';

            fetch('<?php echo site_url("zke/connect"); ?>?ip=' + ip)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusDiv.innerHTML = '<span class="text-success">✅ ' + data.message + '</span>';
                        
                        // Load users
                        fetch('<?php echo site_url("zke/get_users"); ?>')
                            .then(response => response.json())
                            .then(userData => {
                                btn.disabled = false;
                                btn.innerHTML = '<i class="fa fa-link"></i> Connect & Sync';
                                
                                if (userData.success) {
                                    userSelect.innerHTML = '<option value="">All Users</option>';
                                    userData.users.forEach(user => {
                                        var option = document.createElement('option');
                                        option.value = user.userid;
                                        option.text = user.name + ' (ID: ' + user.userid + ')';
                                        userSelect.appendChild(option);
                                    });
                                    document.getElementById('date-section').style.display = 'block';
                                    
                                    // Trigger Dashboard Load
                                    loadLiveStats();
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