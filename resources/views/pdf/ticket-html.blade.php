<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Report - {{ $ticket->No_Ticket }}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10px; 
            margin: 10px; 
        }
        .header { 
            text-align: center; 
            font-size: 14px; 
            font-weight: bold; 
            margin-bottom: 5px; 
        }
        .subheader { 
            text-align: center; 
            font-size: 12px; 
            margin-bottom: 10px; 
        }
        h3 { 
            font-size: 11px; 
            margin: 5px 0; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 5px 0; 
            font-size: 9px; 
        }
        th, td { 
            border: 1px solid #000; 
            padding: 6px; 
            text-align: left; 
            vertical-align: top; 
        }
        th { 
            background-color: #f2f2f2; 
            font-weight: bold; 
            text-align: center; 
        }
        p { 
            margin: 5px 0; 
            font-size: 9px; 
        }
        .footer { 
            text-align: center; 
            font-size: 8px; 
            margin-top: 10px; 
        }
        .escalation-target { 
            margin-left: 5px; 
            font-style: italic; 
        }
        .problem-summary-box, .action-summary-box {
            border: 1px solid #000;
            padding: 8px;
            margin: 5px 0;
            background-color: #f9f9f9;
        }
        .timer-table {
            border-collapse: collapse;
            width: 100%;
        }
        .timer-table td {
            vertical-align: top;
            border: 1px solid #000;
            text-align: center; /* Center-align timer information */
        }
        .timer-table .duration-row td {
            border-bottom: 1px solid #000;
            padding-bottom: 6px;
        }
        .timer-label {
            font-weight: bold;
            color: #333;
            font-size: 9px;
        }
        .timer-value {
            font-family: monospace;
            font-size: 10px;
        }
        .timer-info {
            font-size: 8px;
            color: #666;
            text-align: center; /* Center-align timer info */
        }
        .timer-info-date {
            font-weight: bold;
        }
        .command-output {
            font-family: 'Courier New', Courier, monospace;
            font-size: 9px;
            white-space: pre-wrap;
            text-align: left;
            padding: 4px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">Trouble Ticket System</div>
    <div class="subheader">Problem Status: {{ strtoupper($ticket->Status) }}</div>

    <h3>Ticket Information</h3>
    <table>
        <tr>
            <th width="20%">Nomor Ticket</th><td width="30%">{{ $ticket->No_Ticket }}</td>
            <th width="20%">Customer</th><td width="30%">{{ $ticket->Customer }}</td>
        </tr>
        <tr>
            <th>NOC</th><td>{{ $ticket->openedBy?->name ?? '-' }}</td>
            <th>PIC Name</th><td>{{ $ticket->Pic ?? '-' }}</td>
        </tr>
        <tr>
            <th>Nomor Tlp PIC</th><td>{{ $ticket->Tlp_Pic ?? '-' }}</td>
            <th>Lokasi Toko</th><td>{{ $ticket->remote?->Nama_Toko ?? '-' }}</td>
        </tr>
        <tr>
            <th>Report Problem Date</th><td>{{ $ticket->Open_Time?->format('Y-m-d H:i:s') ?? '-' }}</td>
            <th>Kode Toko</th><td>{{ $ticket->remote?->Site_ID ?? '-' }}</td>
        </tr>
        <tr>
            <th>Category</th><td>{{ $ticket->Catagory }}</td>
            <th>Classification</th><td>{{ $ticket->Classification ?? '-' }}</td>
        </tr>
        <tr>
            <th>Open By</th><td>{{ $ticket->openedBy?->name ?? '-' }}</td>
            <th>Closed By</th><td>{{ $ticket->closedBy?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Pending By</th>
            <td>
                @php
                    $pendingAction = $actions->firstWhere('Action_Taken', 'Pending Clock');
                @endphp
                {{ $pendingAction ? $pendingAction->Action_By : '-' }}
            </td>
            <th>Open Level</th>
            <td>
                @php
                    $levelOrder = [
                        'Level 0' => ['order' => 0, 'role' => 'Admin'],
                        'Level 1' => ['order' => 1, 'role' => 'NOC'],
                        'Level 2' => ['order' => 2, 'role' => 'SPV NOC'],
                        'Level 3' => ['order' => 3, 'role' => 'Teknisi'],
                        'Level 4' => ['order' => 4, 'role' => 'SPV Teknisi'],
                        'Level 5' => ['order' => 5, 'role' => 'Engineer'],
                        'Level 6' => ['order' => 6, 'role' => 'Management'],
                    ];
                    $openLevel = $ticket->Open_Level ?? ($ticket->openedBy?->Level ?? 'Level 1');
                    $openLevelRole = isset($levelOrder[$openLevel]) ? $levelOrder[$openLevel]['role'] : $openLevel;
                @endphp
                {{ $openLevelRole }}
            </td>
        </tr>
        <tr>
            <th>Eskalasi</th>
            <td colspan="3">
                @php
                    $escalationAction = $actions->where('Action_Taken', 'Escalation')->sortByDesc('Action_Time')->first();
                    $escalationRole = $escalationAction && isset($levelOrder[$escalationAction->Action_Level])
                        ? $levelOrder[$escalationAction->Action_Level]['role']
                        : null;
                    $escalationTarget = $escalationAction ? (preg_match('/Escalated to: (.*)/', $escalationAction->Action_Description, $matches) ? $matches[1] : null) : null;
                @endphp
                {{ $escalationRole ? 'Escalated to ' . $escalationRole : '-' }}
                @if($escalationTarget)
                    <span class="escalation-target">(To: {{ $escalationTarget }})</span>
                @endif
            </td>
        </tr>
    </table>

    <!-- Timer Information -->
    @php
        $timer = $ticket->getCurrentTimer(true);
        $formatTime = function($seconds) {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $secs = $seconds % 60;
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        };
        $openTime = $formatTime($timer['open']['seconds']);
        $pendingTime = $formatTime($timer['pending']['seconds']);
        $totalTime = $formatTime($timer['total']['seconds']);
    @endphp
    <h3>Timer Information</h3>
    <table class="timer-table">
        <tr class="duration-row">
            <td width="33%">
                <div class="timer-label">Open Duration</div>
                <div class="timer-value">{{ $openTime }}</div>
            </td>
            <td width="33%">
                <div class="timer-label">Pending Duration</div>
                <div class="timer-value">{{ $pendingTime }}</div>
                <div class="timer-info">Pending Start: <span class="timer-info-date">{{ $ticket->Pending_Start?->format('Y-m-d H:i:s') ?? 'N/A' }}</span></div>
                <div class="timer-info">Pending Stop: <span class="timer-info-date">{{ $ticket->Pending_Stop?->format('Y-m-d H:i:s') ?? 'N/A' }}</span></div>
            </td>
            <td width="33%">
                <div class="timer-label">Total Duration</div>
                <div class="timer-value">{{ $totalTime }}</div>
            </td>
        </tr>
    </table>
    
    <table class="timer-table">
        <tr>
            <td width="50%">
                <div class="timer-info"><strong>Open At: 
                    <span class="timer-info-date">{{ $ticket->Open_Time?->format('Y-m-d H:i:s') }}</span>
                </strong></div>
            </td>
            <td width="50%">
                @if($ticket->Status === 'CLOSED')
                    <div class="timer-info"><strong>Closed At: 
                        <span class="timer-info-date">{{ $ticket->Closed_Time?->format('Y-m-d H:i:s') }}</span>
                    </strong></div>
                @endif
            </td>
        </tr>
    </table>

    <h3>Problem Description</h3>
    <p>{{ $ticket->Problem ?? 'No description' }}</p>

    <h3>Action Taken History</h3>
    <table>
        <tr>
            <th width="15%">Date/Time</th> <!-- Reduced width -->
            <th width="12%">Action Taken</th> <!-- Reduced width -->
            <th width="8%">By</th> <!-- Reduced width, as it only contains initials -->
            <th width="15%">Action Level</th> <!-- Reduced width -->
            <th width="50%">Action Description</th> <!-- Increased width to accommodate longer content -->
        </tr>
        @foreach ($actions as $action)
            @php
                $role = isset($levelOrder[$action->Action_Level]) ? $levelOrder[$action->Action_Level]['role'] : ($action->Action_Level ?? 'Unknown');
                $escalationTarget = ($action->Action_Taken === 'Escalation' && preg_match('/Escalated to: (.*)/', $action->Action_Description, $matches)) ? $matches[1] : null;
                $displayDescription = $escalationTarget ? trim(str_replace("Escalated to: $escalationTarget", '', $action->Action_Description)) : $action->Action_Description;
                $isCommandOutput = 
                    preg_match('/(\\r\\n|\\n|\\r|\\t)/', $action->Action_Description) || 
                    preg_match('/(C:\\\\\\\\|ping |\\/home\\/|reply from|ms TTL=|Pinging|PING|packets transmitted|received)/i', $action->Action_Description) ||
                    preg_match('/(tracert|traceroute|nslookup|dig @|whois|netstat|\\$ |# |> |C:\\\\>)/i', $action->Action_Description);
                $nameParts = explode(' ', $action->Action_By);
                $initials = '';
                foreach ($nameParts as $part) {
                    if (!empty($part)) {
                        $initials .= strtoupper(substr($part, 0, 1));
                    }
                }
            @endphp
            <tr>
                <td>{{ $action->Action_Time?->format('Y-m-d H:i:s') }}</td>
                <td>{{ $action->Action_Taken }}</td>
                <td>{{ $initials }}</td>
                <td>{{ $role }}</td>
                <td>
                    @if($isCommandOutput)
                        <div class="command-output">{{ $displayDescription }}</div>
                    @else
                        {{ $displayDescription }}
                    @endif
                    @if($escalationTarget)
                        <span class="escalation-target">(To: {{ $escalationTarget }})</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    <h3>Problem Summary</h3>
    <div class="problem-summary-box">
        @if($ticket->Status === 'CLOSED')
            {{ $ticket->Problem_Summary ?? 'PROBLEM CLOSED' }}
        @else
            -
        @endif
    </div>

    <h3>Action Summary</h3>
    <div class="action-summary-box">
        @if($ticket->Status === 'CLOSED')
            {{ $ticket->Action_Summry ?? 'No action summary provided' }}
        @else
            -
        @endif
    </div>

    <div class="footer">
        PT. Artacomindo Jejaring Nusa<br>
        Perkantoran Graha Prima Bintara No 8 Jl Terusan I Gusti Ngurah Rai<br>
        www.ajnusa.com
    </div>
</body>
</html>