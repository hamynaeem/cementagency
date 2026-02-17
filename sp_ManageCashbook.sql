-- Create missing sp_ManageCashbook stored procedure for db_cement database
-- This will fix the journal voucher save functionality

USE db_cement;

DELIMITER //

CREATE PROCEDURE sp_ManageCashbook(
    IN p_Date DATE,
    IN p_CustomerID INT,
    IN p_Description TEXT,
    IN p_Debit DECIMAL(15,2),
    IN p_Credit DECIMAL(15,2),
    IN p_RefID VARCHAR(50),
    IN p_RefType INT,
    IN p_FinYearID INT,
    IN p_IsPosted TINYINT,
    IN p_BusinessID INT,
    IN p_UserID VARCHAR(50),
    IN p_VoucherID VARCHAR(50)
)
BEGIN
    DECLARE v_PrevBalance DECIMAL(15,2) DEFAULT 0;
    DECLARE v_NewBalance DECIMAL(15,2) DEFAULT 0;
    DECLARE v_LastVoucherID INT DEFAULT 0;
    
    -- Start transaction
    START TRANSACTION;
    
    -- Get current customer balance (if applicable)
    IF p_CustomerID > 0 THEN
        SELECT COALESCE(Balance, 0) INTO v_PrevBalance 
        FROM customers 
        WHERE CustomerID = p_CustomerID;
        
        -- Calculate new balance
        SET v_NewBalance = v_PrevBalance + p_Credit - p_Debit;
    END IF;
    
    -- Insert the voucher record
    INSERT INTO vouchers (
        Date, CustomerID, Description, Debit, Credit, 
        RefID, RefType, FinYearID, IsPosted, BusinessID, 
        UserID, VoucherID, PrevBalance
    ) VALUES (
        p_Date, p_CustomerID, p_Description, p_Debit, p_Credit,
        p_RefID, p_RefType, p_FinYearID, p_IsPosted, p_BusinessID,
        p_UserID, p_VoucherID, v_PrevBalance
    );
    
    -- Get the inserted voucher ID
    SET v_LastVoucherID = LAST_INSERT_ID();
    
    -- Update customer balance if applicable
    IF p_CustomerID > 0 AND EXISTS(SELECT 1 FROM customers WHERE CustomerID = p_CustomerID) THEN
        UPDATE customers 
        SET Balance = v_NewBalance,
            LastUpdated = NOW()
        WHERE CustomerID = p_CustomerID;
    END IF;
    
    -- Update cashbook/ledger if needed
    IF p_RefType = 4 THEN -- Cash transaction
        INSERT IGNORE INTO cashbook (
            Date, VoucherID, CustomerID, Description, 
            Debit, Credit, Balance, FinYearID, BusinessID
        ) VALUES (
            p_Date, v_LastVoucherID, p_CustomerID, p_Description,
            p_Debit, p_Credit, v_NewBalance, p_FinYearID, p_BusinessID
        );
    END IF;
    
    -- Commit transaction
    COMMIT;
    
    -- Return the voucher ID
    SELECT v_LastVoucherID as id, v_LastVoucherID as VoucherID, v_NewBalance as NewBalance;
    
END //

DELIMITER ;

-- Grant execute permissions (adjust user as needed)
-- GRANT EXECUTE ON PROCEDURE sp_ManageCashbook TO 'your_web_user'@'localhost';

-- Test the procedure (optional - remove in production)
-- CALL sp_ManageCashbook('2026-02-16', 713, 'Test Entry', 0, 1000, '0', 4, 1, 0, 1, '1', '100000001');