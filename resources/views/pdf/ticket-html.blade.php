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
            padding: 4px; 
            text-align: left; 
        }
        th { 
            background-color: #f2f2f2; 
            font-weight: bold; 
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
            color: #d97706; 
            margin-left: 5px; 
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
            <th>Open By</th><td>{{ $ticket->openedBy?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Closed By</th><td>{{ $ticket->closedBy?->name ?? '-' }}</td>
            <th>Pending By</th>
            <td>
                @php
                    $pendingAction = $actions->firstWhere('Action_Taken', 'Pending Clock');
                @endphp
                {{ $pendingAction ? $pendingAction->Action_By : '-' }}
            </td>
        </tr>
        <tr>
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
            <th>Eskalasi</th>
            <td>
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

    <h3>Problem Description</h3>
    <p>{{ $ticket->Problem ?? 'No description' }}</p>

    <h3>Action Taken History</h3>
    <table>
        <tr>
            <th width="20%">Date/Time</th>
            <th width="15%">Action Taken</th>
            <th width="15%">By</th>
            <th width="15%">Action Level</th>
            <th width="35%">Action Description</th>
        </tr>
        @foreach ($actions as $action)
            @php
                $role = isset($levelOrder[$action->Action_Level]) ? $levelOrder[$action->Action_Level]['role'] : ($action->Action_Level ?? 'Unknown');
                $escalationTarget = ($action->Action_Taken === 'Escalation' && preg_match('/Escalated to: (.*)/', $action->Action_Description, $matches)) ? $matches[1] : null;
                $displayDescription = $escalationTarget ? trim(str_replace("Escalated to: $escalationTarget", '', $action->Action_Description)) : $action->Action_Description;
            @endphp
            <tr>
                <td>{{ $action->Action_Time?->format('Y-m-d H:i:s') }}</td>
                <td>{{ $action->Action_Taken }}</td>
                <td>{{ $action->Action_By }}</td>
                <td>{{ $role }}</td>
                <td>
                    {{ $displayDescription }}
                    @if($escalationTarget)
                        <span class="escalation-target"> (To: {{ $escalationTarget }})</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    <h3>Problem Summary</h3>
    <p>
        @if($ticket->Status === 'CLOSED')
            {{ $ticket->Problem_Summary ?? 'PROBLEM CLOSED' }}
        @else
            -
        @endif
    </p>

    <div class="footer">
        PT. Artacomindo Jejaring Nusa<br>
        Perkantoran Graha Prima Bintara No 8 Jl Terusan I Gusti Ngurah Rai<br>
        www.ajnusa.com
    </div>
</body>
</html>