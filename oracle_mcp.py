from mcp.server.fastmcp import FastMCP
import oracledb
import json
import os

# Inicializamos el servidor MCP
mcp = FastMCP("Oracle_ICEBERG_PRUEBA")

# Credenciales desde variables de entorno (configurar en .env o sistema)
DB_HOST = os.environ.get("ORACLE_HOST", "localhost")
DB_PORT = os.environ.get("ORACLE_PORT", "1521")
DB_USER = os.environ.get("ORACLE_USER", "")
DB_PASSWORD = os.environ.get("ORACLE_PASS", "")
DB_SERVICE = os.environ.get("ORACLE_SERVICE", "")

def get_connection():
    """Establece la conexión con la base de datos Oracle."""
    dsn = oracledb.makedsn(DB_HOST, DB_PORT, service_name=DB_SERVICE)
    return oracledb.connect(user=DB_USER, password=DB_PASSWORD, dsn=dsn)

@mcp.tool()
def ejecutar_consulta_oracle(query: str) -> str:
    """
    Ejecuta una consulta SQL de SOLO LECTURA (SELECT) en la base de datos Oracle ICEBERG_PRUEBA.
    Útil para explorar el esquema, ver tablas (como ACAD_AUDITORIA, ACAD_CALENDARIO, etc.) y datos de prueba.
    """
    # Medida de seguridad básica para evitar modificaciones accidentales por parte de la IA
    if not query.strip().upper().startswith("SELECT"):
        return "Error de seguridad: Esta herramienta solo permite consultas SELECT de solo lectura."
        
    try:
        with get_connection() as connection:
            with connection.cursor() as cursor:
                cursor.execute(query)
                
                # Obtenemos los nombres de las columnas
                column_names = [col[0] for col in cursor.description]
                
                # Limitamos a 50 resultados para no saturar el contexto de Windsurf
                rows = cursor.fetchmany(50)
                
                # Formateamos el resultado como una lista de diccionarios
                resultado = [dict(zip(column_names, row)) for row in rows]
                
                return json.dumps(resultado, indent=2, default=str)
                
    except Exception as e:
        return f"Error ejecutando la consulta en Oracle: {str(e)}"

if __name__ == "__main__":
    # Inicia el servidor usando la comunicación estándar (stdio) que requiere Windsurf
    mcp.run()