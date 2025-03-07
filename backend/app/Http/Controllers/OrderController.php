<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Exports\OrdersExport;
use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\HistoryOrdersResource;
use App\Imports\OrdersImport;
use App\Models\HistoryOrders;
use App\Models\Order;
use App\Http\Resources\OrderResource;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        // Retrieve the search query from request
        $search = $request->input('search');

        // Build the query
        $query = Order::with(['status:id,status,colorHex', 'deliveryCompany:id,name,colorHex', 'affectedTo:id,name', 'createdBy:id,name'])
            ->orderBy('created_at', 'desc');

        // Apply search filter if a search term exists
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->Where('tracking', 'like', "%{$search}%")
                  ->orWhere('external_id', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('client_lastname', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('product_url', 'like', "%{$search}%")
                  ->orWhereHas('status', function ($q) use ($search) {
                      $q->where('status', 'like', "%{$search}%");
                  })
                  ->orWhereHas('deliveryCompany', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('affectedTo', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('createdBy', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Paginate the results
        $orders = $query->paginate(20);

        // Return the collection as a resource
        return OrderResource::collection($orders);
    }

    public function export()
    {
        return Excel::download(new OrdersExport, 'commandes.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        // Initialize an array to collect errors
        $errors = [];

        try {
            // Import the file and pass the $errors array to collect issues
            Excel::import(new OrdersImport($errors), $request->file('file'));

            // If there are errors, return them to the user
            if (!empty($errors)) {
                return response()->json([
                    'message' => 'Some rows were not imported due to errors.',
                    'errors' => $errors,
                ], 200); // 422 Unprocessable Entity
            }

            // If no errors, return success message
            return response()->json(['message' => 'File imported successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to import file:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to import file. ' . $e->getMessage()], 500);
        }
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $filePath = 'templates/order_template.xlsx'; // Updated path

        try {
            // Generate the file
            Excel::store(new TemplateExport(), $filePath);

            // Get the full path to the file
            $fullPath = storage_path('app/private/' . $filePath);

            // Check if the file exists
            if (!file_exists($fullPath)) {
                throw new \Exception('File not found after generation.');
            }

            // Serve the file for download and delete it afterward
            return response()
                ->download($fullPath, 'order_template.xlsx')
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Handle any errors that occur during file generation
            \Log::error('Failed to generate the template file: ' . $e->getMessage());
            abort(500, 'Failed to generate the template file. Please try again later.');
        }
    }

    public function tasktoday(Request $request)
    {

        // Retrieve the search query from request
        $search = $request->input('search');

        $today = now()->toDateString();

        // Query to retrieve orders
        $orders = Order::with([
            'status:id,status,colorHex',
            'deliveryCompany:id,name,colorHex',
            'affectedTo:id,name',
            'createdBy:id,name',
        ])
        // Exclude specific statuses
        ->where(function ($query) {
            $query->whereHas('status', function ($q) {
                $q->whereNotIn('status', ['Retourné au vendeur', 'Livré']);
            })
            ->orWhere(function ($query) {
                $query->whereHas('status', function ($q) {
                    $q->whereIn('status', ['Retourné au vendeur', 'Livré']);
                })
                ->where('archive', 0); // Include 'Retourné au vendeur' or 'Livré' only if not archived
            });
        })



        ->where(function ($query) use ($today) {
            // Include orders with no historyOrders
            $query->whereDoesntHave('historyOrders')








                // Include orders with historyOrders
                ->orWhereHas('historyOrders', function ($subQuery) use ($today) {
                    $subQuery->where(function ($nestedQuery) use ($today) {
                        // Case 1: All history_judge = false
                        $nestedQuery->whereNotExists(function ($existsQuery) {
                            $existsQuery->selectRaw(1)
                                ->from('history_orders as ho')
                                ->whereColumn('ho.order_id', 'orders.id')
                                ->where('ho.history_judge', true);
                        })

                        // Case 2: At least one history_judge = true
                        ->orWhere(function ($orQuery) use ($today) {
                            $orQuery->whereIn('id', function ($query) {
                                $query->selectRaw('MAX(id)')
                                    ->from('history_orders')
                                    ->where('history_judge', true)
                                    ->groupBy('order_id');
                            })
                            ->where(function ($latestSubQuery) use ($today) {
                                // Exclude if the latest history_order with history_judge = true is from today
                                $latestSubQuery->whereDate('created_at', '!=', $today);
                            });
                        })


                        // Case 3: Latest history_judge = true is from today and user_id_validator is null
                        ->orWhere(function ($orQuery) use ($today) {
                            $orQuery->whereIn('id', function ($query) {
                                $query->selectRaw('MAX(id)')
                                    ->from('history_orders')
                                    ->where('history_judge', true)
                                    ->groupBy('order_id');
                            })
                            ->whereDate('created_at', '=', $today)
                            ->whereNull('user_id_validator');
                        });
                    });
                });

        })


        // Order by latest update
        ->orderBy('created_at', 'desc');



        // Apply search filter if a search term exists
        if ($search) {
            $orders->where(function ($q) use ($search) {
                $q->Where('tracking', 'like', "%{$search}%")
                  ->orWhere('external_id', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('client_lastname', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('product_url', 'like', "%{$search}%")
                  ->orWhereHas('status', function ($q) use ($search) {
                      $q->where('status', 'like', "%{$search}%");
                  })
                  ->orWhereHas('deliveryCompany', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('affectedTo', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('createdBy', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }




        $orders = $orders->paginate(20);

        // Return the collection as a resource
        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request)
    {
        try {
            // Extract validated data from the request
            $validatedData = $request->validated();

            // Create the order using the validated data
            $order = Order::create([
                'delivery_company_id' => $validatedData['deleveryCompany'],
                'tracking' => $validatedData['tracking'],
                'external_id' => $validatedData['external_id'],
                'client_name' => $validatedData['client_name'],
                'client_lastname' => $validatedData['client_lastname'] ?? null, // Nullable
                'phone' => $validatedData['phone'],
                'affected_to' => $validatedData['affected_to'],
                'created_by' => auth()->id(), // Use the authenticated user's ID
                'status_id' => 1, // Default status
                'product_url' => $validatedData['product_url'] ?? null, // Nullable
            ]);

            // Return a success response
            return response()->json([
                'message' => 'Order created successfully!',
                'order' => $order,
            ], 201);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Order Creation Failed: ' . $e->getMessage());

            // Return an error response
            return response()->json([
                'message' => 'Failed to create order. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateOrderRequest $request, Order $order)
    {
        // Get validated data
        $validated = $request->validated();

        // Update the order with validated data
        $order->update([
            'delivery_company_id' => $validated['deleveryCompany'], // Adjusted field name
            'tracking' => $validated['tracking'],
            'external_id' => $validated['external_id'],
            'client_name' => $validated['client_name'],
            'client_lastname' => $validated['client_lastname'] ?? null, // Use null if not provided
            'phone' => $validated['phone'],
            'affected_to' => $validated['affected_to'],
            'product_url' => $validated['product_url'] ?? null, // Use null if not provided
        ]);

        // Return a success response (e.g., JSON or redirect)
        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order,
        ]);
    }

    public function updateArchive(Request $request, Order $order)
    {
        // Validate the request (optional)
        $request->validate([
            'archive' => 'required|boolean',
        ]);

        // Update the archive status
        $order->update([
            'archive' => $request->archive,
        ]);

        // Return a response
        return response()->json([
            'message' => 'Order archive status updated successfully.',
            'order' => $order,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'statusId' => 'required|exists:status_orders,id', // 'status_orders' should be the table name for status options
        ]);

        // If validation fails, return errors
        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()
            ], 400);
        }

        // Update the order's status
        try {
            $order->status_id = $request->statusId; // Ensure 'statusId' is correctly used here
            $order->save();

            return response()->json([
                'message' => 'Order status updated successfully',
                'order' => $order // You can return the updated order details if needed
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update order status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getOrderHistory(Order $order)
    {
        $historyOrders = HistoryOrders::with(['reason', 'agent'])
        ->where('order_id', $order->id)
        ->orderBy('id', 'desc')
        ->get();

        if ($historyOrders->isEmpty()) {
            return response()->json([
                'message' => 'No history orders found for this order.',
            ], 404);
        }

        return response()->json([
            'data' => HistoryOrdersResource::collection($historyOrders),
            'message' => 'History orders retrieved successfully.',
        ], 200);
    }

    public function destroy(Order $order)
    {
        try {
            // Check if the order can be deleted :
            if ($order->status_id !== 1) {
                return response()->json([
                    'message' => 'Only orders with "En préparation status" can be deleted.',
                ], 403); // Forbidden
            }


            // Delete the order
            $order->delete();

            // Return a success response
            return response()->json([
                'message' => 'Order deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Order Deletion Failed: ' . $e->getMessage());

            // Return an error response
            return response()->json([
                'message' => 'Failed to delete order. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
