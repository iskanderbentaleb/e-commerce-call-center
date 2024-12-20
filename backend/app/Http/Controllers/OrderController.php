<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Http\Resources\OrderResource;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        // Retrieve the search query from request
        $search = $request->input('search');

        // Build the query
        $query = Order::with(['status:id,status,colorHex', 'deliveryCompany:id,name,colorHex', 'affectedTo:id,name', 'createdBy:id,name'])
            ->orderBy('id', 'desc');

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
        $orders = $query->paginate(10);

        // Return the collection as a resource
        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
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



    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
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
        ]);

        // Return a success response (e.g., JSON or redirect)
        return response()->json([
            'message' => 'Order updated successfully',
            'order' => $order,
        ]);
    }



    // update status orders
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
    // update status orders



    public function getOrderHistory($orderId)
    {
        // Fetch all history orders for the given order ID with related data
        $historyOrders = HistoryOrders::with(['reason', 'agent', 'order'])
            ->where('order_id', $orderId)
            ->get();

        // Check if there are history orders
        if ($historyOrders->isEmpty()) {
            return response()->json([
                'message' => 'No history orders found for this order.',
            ], 404);
        }

        return response()->json([
            'data' => $historyOrders,
            'message' => 'History orders retrieved successfully.',
        ], 200);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        try {
            // Check if the order can be deleted
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
