package icat;


import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.sql.PreparedStatement;

import katalyzeapp.DatabaseNotificationConfig;

public class AnalystMessageSource {
	DatabaseNotificationConfig config;
	Connection db;
	
	int lastReadMessageId = -1;
	
	
	public AnalystMessageSource(DatabaseNotificationConfig config) {
		this.config = config;
	}
	
	public void open() throws Exception {
		db = config.createConnection();
	}
	
	public ArrayList<AnalystMessage> getNewMessages(int contestTime) throws SQLException {
		PreparedStatement s = db.prepareStatement("select * from entries where user <> 'katalyzer' and contest_time <= ? and id > ?");
		s.setInt(1, contestTime);
		s.setInt(2, lastReadMessageId);
		ResultSet results = s.executeQuery();
		
		ArrayList<AnalystMessage> messages = new ArrayList<AnalystMessage>();
		while (results.next()) {
			AnalystMessage message = AnalystMessage.fromSQL(results);
			if (message.id > lastReadMessageId) {
				lastReadMessageId = message.id;
			}
			messages.add(message);
		}
		return messages;
	}

}