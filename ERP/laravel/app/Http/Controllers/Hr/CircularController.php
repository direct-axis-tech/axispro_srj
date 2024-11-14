<?php

namespace App\Http\Controllers\Hr;

use App\Events\Hr\CircularIssued;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\EntityGroup;
use App\Models\EntityGroupCategory;
use App\Models\Hr\Circular;
use App\Models\Hr\CircularAcknowledgement;
use App\Models\Hr\Employee;
use App\Models\MetaReference;
use App\Models\System\AccessRole;
use App\Permissions;
use DB;
use File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Storage;
use Yajra\DataTables\QueryDataTable;

class CircularController extends Controller
{
    public function index()
    {
        abort_unless(
            authUser()->hasPermission(Permissions::HRM_MANAGE_CIRCULAR),
            403
        );

        $entityTypes = Entity::whereIn('id', [Entity::USER, Entity::EMPLOYEE, Entity::GROUP, Entity::ACCESS_ROLE])->get();
        $entityGroups = EntityGroup::whereCategory(EntityGroupCategory::WORK_FLOW_RELATED)->get();
        $authorizedEmployees = Employee::active()->get();
        $accessRoles = AccessRole::active()->select('id', 'role', 'role as name')->get();
        $users = DB::table('0_users')
                    ->select(
                        'id',
                        DB::raw("concat(user_id, if(real_name = '', '', concat(' - ', `real_name`))) as name")
                    )
                    ->where('inactive', 0)
                    ->orderBy('real_name')
                    ->get();

        return view('hr.circulars', compact('entityTypes', 'entityGroups', 'authorizedEmployees', 'accessRoles', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        abort_unless(
            authUser()->hasPermission(Permissions::HRM_MANAGE_CIRCULAR),
            403
        );

        $inputs = $this->getValidatedInputs($request);

        $circular = DB::transaction(function() use($inputs) {

            $refNumber = MetaReference::getNext(Circular::CIRCULAR);

            $filePath = $inputs['file'][0]->store("docs/hr/circular");

            $circular = new Circular();
            $circular->reference      = $refNumber;
            $circular->entity_type_id = $inputs['entity_type_id'];
            $circular->memo           = $inputs['memo'];
            $circular->circular_date  = $inputs['circular_date'];
            $circular->file           = $filePath;
            $circular->created_by     = authUser()->id;

            // Assign the appropriate entity ID based on the selected entity type
            switch ($inputs['entity_type_id']) {
                case Entity::USER:
                    $circular->entity_id = json_encode($inputs['user_id']);
                    break;
                case Entity::EMPLOYEE:
                    $circular->entity_id = json_encode($inputs['employee_id']);
                    break;
                case Entity::GROUP:
                    $circular->entity_id = json_encode($inputs['entity_group_id']);
                    break;
                case Entity::ACCESS_ROLE:
                    $circular->entity_id = json_encode($inputs['access_role_id']);
                    break;
            }

            $circular->save();

            return $circular;
        });

        Event::dispatch(new CircularIssued($circular));

        return response()->json([
            'message' => 'Circular Created Successfully.'
        ], Response::HTTP_CREATED);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Hr\Circular $circular
     * @return \Illuminate\Http\Response
     */
    public function destroy(Circular $circular)
    {
        abort_unless(
            authUser()->hasPermission(Permissions::HRM_MANAGE_CIRCULAR),
            403
        );

        $result = DB::transaction(function () use ($circular) {

            $circular->update(['inactive' => true]);

            return response()->json(['message' => 'Circular Deleted Successfully']);
        });

        return $result;
    }

    /**
     * Validate the inputs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function getValidatedInputs(Request $request)
    {
        $inputs = $request->validate([
            'entity_type_id'    => 'required|integer|in:' . implode(',', [Entity::USER, Entity::EMPLOYEE, Entity::GROUP, Entity::ACCESS_ROLE]),
            'user_id'           => 'nullable|required_if:entity_type_id,'.Entity::USER.'|array',
            'user_id.*'         => 'nullable|required_if:entity_type_id,'.Entity::USER.'|integer|exists:0_users,id',
            'employee_id'       => 'nullable|required_if:entity_type_id,'.Entity::EMPLOYEE.'|array',
            'employee_id.*'     => 'nullable|required_if:entity_type_id,'.Entity::EMPLOYEE.'|integer|exists:0_employees,id',
            'entity_group_id'   => 'nullable|required_if:entity_type_id,'.Entity::GROUP.'|array',
            'entity_group_id.*' => 'nullable|required_if:entity_type_id,'.Entity::GROUP.'|integer|exists:0_entity_groups,id',
            'access_role_id'    => 'nullable|required_if:entity_type_id,'.Entity::ACCESS_ROLE.'|array',
            'access_role_id.*'  => 'nullable|required_if:entity_type_id,'.Entity::ACCESS_ROLE.'|integer|exists:0_security_roles,id',
            'circular_date'     => 'required|date_format:j-M-Y',
            'memo'              => 'required|string',
            'file'              => 'required|array|size:1',
            'file.*'            => 'required|file|mimetypes:application/pdf|max:2048'
        ]);
        
        $inputs['circular_date'] = date2sql($inputs['circular_date']);

        return Arr::only($inputs, [
            'entity_type_id',
            'user_id',
            'employee_id',
            'entity_group_id',
            'access_role_id',
            'circular_date',
            'memo',
            'file'
        ]);
    }

    /**
     * Returns the dataTable api for this resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function dataTable()
    {
        abort_unless(
            authUser()->hasPermission(Permissions::HRM_MANAGE_CIRCULAR),
            403
        );

        $mysqlDateFormat = getDateFormatForMySQL();

        $builder = Circular::select('0_circulars.*', 'creator.real_name as issued_by',
                        DB::raw(
                            "COALESCE(
                                GROUP_CONCAT(DISTINCT 0_users.real_name SEPARATOR', '), 
                                GROUP_CONCAT(DISTINCT 0_employees.name SEPARATOR', '), 
                                GROUP_CONCAT(DISTINCT 0_entity_groups.name SEPARATOR', '),
                                GROUP_CONCAT(DISTINCT (CONVERT(0_security_roles.`role` USING utf8mb4) COLLATE utf8mb4_general_ci) SEPARATOR', ')
                            ) AS entity_name"),
                        DB::raw("date_format(`circular_date`, '{$mysqlDateFormat}') as formatted_circular_date"),
                    )
                    ->leftJoin('0_users', function($join) {
                        $join->whereRaw("JSON_CONTAINS(0_circulars.entity_id, JSON_QUOTE(CONCAT('', 0_users.id)))")
                            ->where('0_circulars.entity_type_id', Entity::USER);
                    })
                    ->leftJoin('0_employees', function($join) {
                        $join->whereRaw("JSON_CONTAINS(0_circulars.entity_id, JSON_QUOTE(CONCAT('', 0_employees.id)))")
                            ->where('0_circulars.entity_type_id', Entity::EMPLOYEE);
                    })
                    ->leftJoin('0_entity_groups', function($join) {
                        $join->whereRaw("JSON_CONTAINS(0_circulars.entity_id, JSON_QUOTE(CONCAT('', 0_entity_groups.id)))")
                            ->where('0_circulars.entity_type_id', Entity::GROUP);
                    })
                    ->leftJoin('0_security_roles', function($join) {
                        $join->whereRaw("JSON_CONTAINS(0_circulars.entity_id, JSON_QUOTE(CONCAT('', 0_security_roles.id)))")
                            ->where('0_circulars.entity_type_id', Entity::ACCESS_ROLE);
                    })
                    ->leftJoin('0_users as creator', 'creator.id', '0_circulars.created_by')
                    ->where('0_circulars.inactive', 0)
                    ->groupBy('0_circulars.id')
                    ->orderBy('0_circulars.circular_date');
        
        $dataTable = (new QueryDataTable(DB::query()->fromSub($builder, 't')))
                    ->addColumn('entity_type_name', function($row) {
                        return Entity::find($row->entity_type_id)->name;
                    });

        return $dataTable->toJson();
    }

    /**
     * Display a listing of the resource .
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function issuedCirculars(Request $request)
    {
        abort_unless(authUser()->hasAnyPermission(
            Permissions::HRM_VIEW_ISSUED_CIRCULAR,
            Permissions::HRM_DOWNLOAD_ISSUED_CIRCULAR
        ), 403);

        $canAccess =  [
            'VIEW' => authUser()->hasPermission(Permissions::HRM_VIEW_ISSUED_CIRCULAR),
            'DOWNLOAD' => authUser()->hasAnyPermission(Permissions::HRM_DOWNLOAD_ISSUED_CIRCULAR)
        ];

        $mysqlDateFormat = getDateFormatForMySQL();

        $builder =  Circular::select('0_circulars.*', 'ack.id as acknowledgement_id', 'creator.real_name as issued_by', 
                            DB::raw("date_format(`circular_date`, '{$mysqlDateFormat}') as formatted_circular_date")
                        )
                        ->leftJoin('0_circular_acknowledgement_details as ack', function($join) {
                            $join->on('ack.circular_id', '=', '0_circulars.id')
                                 ->where('ack.acknowledged_by', authUser()->id);                              
                        })
                        ->leftJoin('0_users', function($join) {
                            $join->whereRaw("JSON_CONTAINS(0_circulars.entity_id, JSON_QUOTE(CONCAT('', 0_users.id)))")
                                ->where('0_circulars.entity_type_id', Entity::USER);
                        })
                        ->leftJoin('0_employees', function($join) {
                            $join->whereRaw("JSON_CONTAINS(0_circulars.entity_id, JSON_QUOTE(CONCAT('', 0_employees.id)))")
                                ->where('0_circulars.entity_type_id', Entity::EMPLOYEE);
                        })
                        ->leftJoin('0_entity_groups', function($join) {
                            $join->whereRaw("JSON_CONTAINS(0_circulars.entity_id, JSON_QUOTE(CONCAT('', 0_entity_groups.id)))")
                            ->where('0_circulars.entity_type_id', Entity::GROUP);
                        })
                        ->leftJoin('0_security_roles', function($join) {
                            $join->whereRaw("JSON_CONTAINS(0_circulars.entity_id, JSON_QUOTE(CONCAT('', 0_security_roles.id)))")
                            ->where('0_circulars.entity_type_id', Entity::ACCESS_ROLE);
                        })
                        ->leftJoin('0_users AS employee_users', 'employee_users.employee_id', '=', '0_employees.id')
                        ->leftJoin('0_users as group_users', 'group_users.flow_group_id', '0_entity_groups.id')
                        ->leftJoin('0_users as role_users', 'role_users.role_id', '0_security_roles.id')
                        ->leftJoin('0_users as creator', 'creator.id', '0_circulars.created_by')
                        ->where('0_circulars.inactive', 0)
                        ->groupBy('0_circulars.id');

        $builder->where(function (Builder $builder) {   

            $builder->where('0_circulars.created_by', authUser()->id);

            $builder->orWhere(function (Builder $query) {
                        $query->where('0_circulars.entity_type_id', Entity::USER)
                            ->where('0_users.id', authUser()->id)
                            ->where('0_users.inactive', 0);
            });

            $builder->orWhere(function (Builder $query) {
                $query->where('0_circulars.entity_type_id', Entity::EMPLOYEE)
                    ->where('employee_users.id', authUser()->id)
                    ->where('employee_users.inactive', 0);
            });

            $builder->orWhere(function (Builder $query) {
                $query->where('0_circulars.entity_type_id', Entity::GROUP)
                    ->where('group_users.id', authUser()->id)
                    ->where('group_users.inactive', 0);
            });

            $builder->orWhere(function (Builder $query) {
                $query->where('0_circulars.entity_type_id', Entity::ACCESS_ROLE)
                    ->where('role_users.id', authUser()->id)
                    ->where('role_users.inactive', 0);
            });

        });

        if ($request->input('reference')) {
            $builder = $builder->where('reference', $request->reference);
        }

        if ($request->input('circular_date_from')) {
            $builder = $builder->where('circular_date', '>=', date2sql($request->circular_date_from));
        }

        if ($request->input('circular_date_to')) {
            $builder = $builder->where('circular_date', '<=', date2sql($request->circular_date_to));
        }

        $resultList = $builder->paginate(15);
        $userInputs = $request->input();

        return view('hr.issuedCirculars', compact('resultList', 'userInputs', 'canAccess'));
    }

    /**
     * Store Acknowledgement in storage .
     * 
     * @param  \Illuminate\Http\Circular $circular
     * @return \Illuminate\Http\Response
     */
    public function acknowledge(Request $request, Circular $circular)
    {
        abort_unless(authUser()->hasAnyPermission(
            Permissions::HRM_VIEW_ISSUED_CIRCULAR,
            Permissions::HRM_DOWNLOAD_ISSUED_CIRCULAR
        ), 403);

        // Check if the user has already acknowledged the circular
        $acknowledgeStatus = CircularAcknowledgement::where('circular_id', $circular->id)
                                ->where('acknowledged_by', authUser()->id)
                                ->exists();
    
        if ($acknowledgeStatus) {
            return response()->json(['error' => 'Circular already acknowledged by the user'], 422);
        }
    
        $acknowledge = new CircularAcknowledgement();
        $acknowledge->circular_id = $circular->id;
        $acknowledge->acknowledged_by = authUser()->id;
        $acknowledge->save();

        return response()->json(['message' => 'Circular Acknowledged Successfully']);

    }

    /**
     * Get the circular acknowledgement status of the addressees in storage .
     * 
     * @param  \Illuminate\Http\Circular $circular
     * @return \Illuminate\Http\Response
     */
    public function getStatus(Request $request, Circular $circular)
    {
        abort_unless(
            authUser()->hasPermission(Permissions::HRM_MANAGE_CIRCULAR),
            403
        );

        $mysqlDateFormat = getDateFormatForMySQL();

        $subquery = DB::table('0_circulars AS sub_c')
            ->select([
                'sub_c.id as circular_id',
                'sub_c.reference',
                'sub_c.entity_type_id',
                'sub_c.entity_id',
                DB::raw('CASE 
                            WHEN sub_c.entity_type_id = '. Entity::USER .' 
                                THEN GROUP_CONCAT(DISTINCT 0_users.id)
                            WHEN sub_c.entity_type_id = '. Entity::EMPLOYEE .' 
                                THEN GROUP_CONCAT(DISTINCT employee_users.id)
                            WHEN sub_c.entity_type_id = '. Entity::GROUP .' 
                                THEN GROUP_CONCAT(DISTINCT group_users.id)
                            WHEN sub_c.entity_type_id = '. Entity::ACCESS_ROLE .' 
                                THEN GROUP_CONCAT(DISTINCT role_users.id)
                        END AS notified_user_id')
            ])
            ->leftJoin('0_users', function ($join) {
                $join->whereRaw("JSON_CONTAINS(sub_c.entity_id, JSON_QUOTE(CONCAT('', 0_users.id)))")
                    ->where('sub_c.entity_type_id', Entity::USER);
            })
            ->leftJoin('0_employees', function ($join) {
                $join->whereRaw("JSON_CONTAINS(sub_c.entity_id, JSON_QUOTE(CONCAT('', 0_employees.id)))")
                    ->where('sub_c.entity_type_id', Entity::EMPLOYEE);
            })
            ->leftJoin('0_entity_groups', function ($join) {
                $join->whereRaw("JSON_CONTAINS(sub_c.entity_id, JSON_QUOTE(CONCAT('', 0_entity_groups.id)))")
                    ->where('sub_c.entity_type_id', Entity::GROUP);
            })
            ->leftJoin('0_security_roles', function ($join) {
                $join->whereRaw("JSON_CONTAINS(sub_c.entity_id, JSON_QUOTE(CONCAT('', 0_security_roles.id)))")
                    ->where('sub_c.entity_type_id', Entity::ACCESS_ROLE);
            })
            ->leftJoin('0_users AS employee_users', 'employee_users.employee_id', '=', '0_employees.id')
            ->leftJoin('0_users AS group_users', 'group_users.flow_group_id', '=', '0_entity_groups.id')
            ->leftJoin('0_users AS role_users', 'role_users.role_id', '=', '0_security_roles.id')
            ->where('sub_c.id', '=', $circular->id);

        $builder = DB::table('0_circulars')
            ->select([
                'users.real_name AS notified_users',
                DB::raw('CASE WHEN ack.id IS NOT NULL THEN "Acknowledged" ELSE "Not Acknowledged" END AS notification_status'),
                'ack.created_at',
            ])
            ->fromSub($subquery, 'circular_notified_users')
            // ->leftJoin('0_users as users', function($join){
            //     $join->whereIn('users.id', 'circular_notified_users.notified_user_id');
            // })
            ->leftJoin('0_users as users', function($join) {
                $join->on(DB::raw("FIND_IN_SET(users.id, circular_notified_users.notified_user_id)"), '>', DB::raw('0'))
                    ->where('users.inactive', 0);
            })           
            ->leftJoin('0_circular_acknowledgement_details AS ack', function($join) {
                $join->on('ack.circular_id', '=', 'circular_notified_users.circular_id')
                    ->on('ack.acknowledged_by', 'users.id');
            });

        $dataTable = (new QueryDataTable(DB::query()->fromSub($builder, 't')));

        return $dataTable->toJson();

    }
    
    /**
     * Get Circualr Details In a Secure Way.
     * 
     * @param  \Illuminate\Http\Circular $circular
     * @return \Illuminate\Http\Response
     */
    public function viewSecureFile(Circular $circular)
    {
        abort_unless(
            authUser()->hasPermission(Permissions::HRM_VIEW_ISSUED_CIRCULAR),
            403
        );

        $filePath = storage_path('app/'. $circular->file);

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $circular->file . '"',
        ]);
    }

    /**
     * Download Circualr Details In a Secure Way.
     * 
     * @param  \Illuminate\Http\Circular $circular
     * @return \Illuminate\Http\Response
     */
    public function downloadSecureFile(Circular $circular)
    {
        abort_unless(
            authUser()->hasPermission(Permissions::HRM_DOWNLOAD_ISSUED_CIRCULAR),
            403
        );

        $filePath = $circular->file;

        if (!Storage::exists($filePath)) {
            $filePath = "/circular/{$filePath}";
        }

        abort_unless(Storage::exists($filePath), 404);
        $ext = File::extension(storage_path($filePath));

        return Storage::download(
            $filePath,
            'circular_'.date('YmdHis').".$ext"
        );
    }

}
